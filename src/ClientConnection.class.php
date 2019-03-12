<?php
namespace Phpcraft;
/** A server-to-client connection. */
class ClientConnection extends Connection
{
	/**
	 * The hostname the client had connected to.
	 * @see ClientConnection::getHost
	 * @see ClientConnection::handleInitialPacket
	 * @var string $hostname
	 */
	public $hostname;
	/**
	 * The port the client had connected to.
	 * @see ClientConnection::handleInitialPacket
	 * @see ClientConnection::getHost
	 * @var number $hostport
	 */
	public $hostport;
	/**
	 * The client's in-game name.
	 * @var string $username
	 */
	public $username;
	/**
	 * The Uuid of the client.
	 * @var Uuid $uuid
	 */
	public $uuid;
	/**
	 * This variable is for servers to keep track of when to send the next keep alive packet to clients.
	 * @var integer $next_heartbeat
	 */
	public $next_heartbeat = 0;
	/**
	 * This variable is for servers to keep track of how long clients have to answer keep alive packets.
	 * @var integer $disconnect_after
	 */
	public $disconnect_after = 0;
	/**
	 * This variable is for servers to keep track of the client's position.
	 * @var Position $pos
	 */
	public $pos;
	/**
	 * This variable is for servers to keep track of the chunks this client has received by storing their coordinates as a string (e.g. "-1:1").
	 * @var array $chunks
	 */
	public $chunks;

	/**
	 * The constructor.
	 * After this, you should call ClientConnection::handleInitialPacket().
	 * @param resource $stream
	 */
	function __construct($stream)
	{
		parent::__construct(-1, $stream);
	}

	/**
	 * Deals with the first packet the client has sent.
	 * This function deals with the handshake or legacy list ping packet.
	 * Errors will cause the connection to be closed.
	 * @return integer Status: 0 = An error occured and the connection has been closed. 1 = Handshake was successfully read; use Connection::$state to see if the client wants to get the status (1) or login to play (2). 2 = A legacy list ping packet has been received.
	 */
	function handleInitialPacket()
	{
		try
		{
			if($this->readRawPacket(0, 1))
			{
				$packet_length = $this->readByte();
				if($packet_length == 0xFE)
				{
					if($this->readRawPacket(0) && $this->readByte() == 0x01 && $this->readByte() == 0xFA && $this->readShort() == 11 && $this->readRaw(22) == mb_convert_encoding("MC|PingHost", "utf-16be"))
					{
						$this->ignoreBytes(2);
						$this->protocol_version = $this->readByte();
						$this->hostname = mb_convert_encoding($this->readRaw($this->readShort() * 2), "utf-8", "utf-16be");
						$this->hostport = $this->readInt();
						return 2;
					}
				}
				else if($this->readRawPacket(0, $packet_length))
				{
					$packet_id = $this->readVarInt();
					if($packet_id === 0x00)
					{
						$this->protocol_version = $this->readVarInt();
						$this->hostname = $this->readString();
						$this->hostport = $this->readShort();
						$this->state = $this->readVarInt();
						if($this->state == 1)
						{
							$this->disconnect_after = microtime(true) + 10;
							return 1;
						}
						else if($this->state == 2)
						{
							if(Phpcraft::isProtocolVersionSupported($this->protocol_version))
							{
								return 1;
							}
							$this->writeVarInt(0x00);
							$this->writeString('{"text":"You\'re using an incompatible version."}');
							$this->send();
						}
					}
				}
			}
		}
		catch(Exception $ignored){}
		$this->close();
		return 0;
	}

	/**
	 * Returns the host the client had connected to, e.g. localhost:25565.
	 * @return string
	 */
	function getHost()
	{
		return $this->hostname.":".$this->hostport;
	}

	/**
	 * Sends an Encryption Request Packet.
	 * @param string $private_key Your OpenSSL private key resource.
	 * @return ClientConnection $this
	 */
	function sendEncryptionRequest($private_key)
	{
		if($this->state == 2)
		{
			$this->writeVarInt(0x01);
			$this->writeString(""); // Server ID
			$this->writeString(base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24)))); // Public Key
			$this->writeString("1337"); // Verify Token
			$this->send();
		}
		return $this;
	}

	/**
	 * Reads an encryption response packet and authenticates with Mojang.
	 * This requires that you've set ClientConnection::$username after the client sent Login Start.
	 * If there is an error, the client is disconnected and false is returned, and on success an array looking like this is returned:
	 * <pre>[
	 *   "id" => "11111111222233334444555555555555",
	 *   "name" => "Notch",
	 *   "properties" => [
	 *     [
	 *       "name" => "textures",
	 *       "value" => "&lt;base64 string&gt;",
	 *       "signature" => "&lt;base64 string; signed data using Yggdrasil's private key&gt;"
	 *     ]
	 *   ]
	 * ]</pre>
	 * After this, you should call ClientConnection::finishLogin().
	 * @param string $private_key Your OpenSSL private key resource.
	 * @return mixed
	 */
	function handleEncryptionResponse($private_key)
	{
		openssl_private_decrypt($this->readString(), $shared_secret, $private_key, OPENSSL_PKCS1_PADDING);
		openssl_private_decrypt($this->readString(), $verify_token, $private_key, OPENSSL_PKCS1_PADDING);
		if($verify_token !== "1337")
		{
			$this->close();
			return false;
		}
		$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
		stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
		stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
		$json = @json_decode(@file_get_contents("https://sessionserver.mojang.com/session/minecraft/hasJoined?username={$this->username}&serverId=".Phpcraft::sha1($shared_secret.base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24))))), true);
		if(!$json || empty($json["id"]) || @$json["name"] !== $this->username)
		{
			$this->writeVarInt(0x00);
			$this->writeString('{"text":"Failed to authenticate against session server."}');
			$this->send();
			$this->close();
			return false;
		}
		return $json;
	}

	/**
	 * Sets the compression threshold and finishes the login.
	 * @param Uuid $uuid The Uuid of the client.
	 * @param string $name The name the client presented in the Login Start packet.
	 * @param Counter $eidCounter The server's Counter to assign an entity ID to the client.
	 * @param integer $compression_threshold Use -1 to disable compression.
	 * @return ClientConnection $this
	 * @see Phpcraft::generateUUIDv4()
	 * @see Phpcraft::addHypensToUUID()
	 */
	function finishLogin(\Phpcraft\Uuid $uuid, $name, \Phpcraft\Counter $eidCounter, $compression_threshold = 256)
	{
		if($this->state == 2)
		{
			if($compression_threshold > -1 || $this->protocol_version < 48)
			{
				$this->writeVarInt(0x03);
				$this->writeVarInt($compression_threshold);
				$this->send();
			}
			$this->compression_threshold = $compression_threshold;
			$this->writeVarInt(0x02);
			$this->writeString(($this->uuid = $uuid)->toString(true));
			$this->writeString($name);
			$this->send();
			$this->chunks = [];
			$this->eid = $eidCounter->next();
			$this->state = 3;
		}
		return $this;
	}

	/**
	 * Disconnects the client with a reason.
	 * @param array $reason The reason of the disconnect; chat object.
	 */
	function disconnect($reason = [])
	{
		if($reason && $this->state > 1)
		{
			try
			{
				if($this->state == 2) // Login
				{
					$this->write_buffer = \Phpcraft\Phpcraft::intToVarInt(0x00);
				}
				else // Play
				{
					$this->startPacket("disconnect");
				}
				$this->writeString(json_encode($reason));
				$this->send();
			}
			catch(Exception $ignored){}
		}
		$this->close();
	}
}
