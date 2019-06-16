<?php
namespace Phpcraft;
use DomainException;
use hellsh\UUID;
use InvalidArgumentException;
/** A server-to-client connection. */
class ClientConnection extends Connection
{
	private $ch;
	private $mh;
	/**
	 * The hostname the client had connected to.
	 *
	 * @see ClientConnection::getHost
	 * @see ClientConnection::handleInitialPacket
	 * @var string $hostname
	 */
	public $hostname;
	/**
	 * The port the client had connected to.
	 *
	 * @see ClientConnection::handleInitialPacket
	 * @see ClientConnection::getHost
	 * @var number $hostport
	 */
	public $hostport;
	/**
	 * The client's in-game name.
	 *
	 * @var string $username
	 */
	public $username;
	/**
	 * The UUID of the client.
	 *
	 * @var UUID $uuid
	 */
	public $uuid;
	/**
	 * This variable is for servers to keep track of when to send the next keep alive packet to clients.
	 *
	 * @var integer $next_heartbeat
	 */
	public $next_heartbeat = 0;
	/**
	 * This variable is for servers to keep track of how long clients have to answer keep alive packets.
	 *
	 * @var integer $disconnect_after
	 */
	public $disconnect_after = 0;
	/**
	 * The client's entity ID.
	 *
	 * @var integer $eid
	 */
	public $eid;
	/**
	 * The client's position.
	 *
	 * @var Position $pos
	 */
	public $pos;
	/**
	 * @var float $yaw
	 */
	public $yaw;
	/**
	 * @var float $pitch
	 */
	public $pitch;
	/**
	 * @var boolean $on_ground
	 * @see ServerOnGroundChangeEvent
	 */
	public $on_ground = false;
	/**
	 * A string array of chunks the client has received.
	 *
	 * @var array $chunks
	 */
	public $chunks = [];
	/**
	 * @var integer $gamemode
	 */
	public $gamemode = Gamemode::SURVIVAL;
	/**
	 * @var boolean $invulnerable
	 * @see ClientConnection::sendAbilities
	 */
	public $invulnerable = false;
	/**
	 * @var boolean $flying
	 * @see ClientConnection::sendAbilities
	 * @see ServerFlyChangeEvent
	 */
	public $flying = false;
	/**
	 * @var boolean $can_fly
	 * @see ClientConnection::sendAbilities
	 */
	public $can_fly = false;
	/**
	 * @var boolean $instant_breaking
	 * @see ClientConnection::sendAbilities
	 * @see ClientConnection::setGamemode
	 */
	public $instant_breaking = false;
	/**
	 * @var float $fly_speed
	 * @see ClientConnection::sendAbilities
	 */
	public $fly_speed = 0.05;
	/**
	 * @var float $walk_speed
	 * @see ClientConnection::sendAbilities
	 */
	public $walk_speed = 0.1;

	/**
	 * After this, you should call ClientConnection::handleInitialPacket().
	 *
	 * @param resource $stream
	 */
	public function __construct($stream)
	{
		parent::__construct(-1, $stream);
	}

	/**
	 * Deals with the first packet the client has sent.
	 * This function deals with the handshake or legacy list ping packet.
	 * Errors will cause the connection to be closed.
	 *
	 * @return integer Status: 0 = An error occured and the connection has been closed. 1 = Handshake was successfully read; use Connection::$state to see if the client wants to get the status (1) or login to play (2). 2 = A legacy list ping packet has been received.
	 */
	public function handleInitialPacket()
	{
		try
		{
			if($this->readRawPacket(0, 1))
			{
				$packet_length = $this->readByte();
				if($packet_length == 0xFE)
				{
					if($this->readRawPacket(0) && $this->readByte() == 0x01 && $this->readByte() == 0xFA && gmp_intval($this->readShort()) == 11 && $this->readRaw(22) == mb_convert_encoding("MC|PingHost", "utf-16be"))
					{
						$this->ignoreBytes(2);
						$this->protocol_version = $this->readByte();
						$this->hostname = mb_convert_encoding($this->readRaw(gmp_intval($this->readShort()) * 2), "utf-8", "utf-16be");
						$this->hostport = gmp_intval($this->readInt());
						return 2;
					}
				}
				else if($this->readRawPacket(0, $packet_length))
				{
					$packet_id = gmp_intval($this->readVarInt());
					if($packet_id === 0x00)
					{
						$this->protocol_version = gmp_intval($this->readVarInt());
						$this->hostname = $this->readString();
						$this->hostport = gmp_intval($this->readShort());
						$this->state = gmp_intval($this->readVarInt());
						if($this->state == 1)
						{
							$this->disconnect_after = microtime(true) + 10;
							return 1;
						}
						else if($this->state == 2)
						{
							return 1;
						}
						else
						{
							$this->disconnect(["text" => "Invalid state: ".$this->state]);
						}
					}
				}
			}
		}
		catch(IOException $ignored)
		{
		}
		$this->close();
		return 0;
	}

	/**
	 * Returns the host the client had connected to, e.g. localhost:25565.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->hostname.":".$this->hostport;
	}

	/**
	 * Sends an Encryption Request Packet.
	 *
	 * @param resource $private_key Your OpenSSL private key resource.
	 * @return ClientConnection $this
	 * @throws IOException
	 */
	public function sendEncryptionRequest($private_key)
	{
		if($this->state == 2)
		{
			$this->write_buffer = Connection::varInt(0x01);
			$this->writeString(""); // Server ID
			$this->writeString(base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24)))); // Public Key
			$this->writeString("1337"); // Verify Token
			$this->send();
		}
		return $this;
	}

	/**
	 * Reads an encryption response packet and starts asynchronous authentication with Mojang.
	 * This requires ClientConnection::$username to be set.
	 * In case of an error, the client is disconnected and false is returned. Otherwise, true is returned, and ClientConnection::handleAuthentication should be regularly called to finish the authentication.
	 *
	 * @param resource $private_key Your OpenSSL private key resource.
	 * @return boolean
	 * @throws IOException
	 */
	public function handleEncryptionResponse($private_key)
	{
		openssl_private_decrypt($this->readString(), $shared_secret, $private_key, OPENSSL_PKCS1_PADDING);
		openssl_private_decrypt($this->readString(), $verify_token, $private_key, OPENSSL_PKCS1_PADDING);
		if($verify_token !== "1337")
		{
			$this->close();
			return false;
		}
		$opts = [
			"mode" => "cfb",
			"iv" => $shared_secret,
			"key" => $shared_secret
		];
		stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
		stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
		$this->ch = curl_init("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$this->username."&serverId=".Phpcraft::sha1($shared_secret.base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24)))));
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		if(Phpcraft::isWindows())
		{
			curl_setopt($this->ch, CURLOPT_CAINFO, __DIR__."/cacert.pem");
		}
		$this->mh = curl_multi_init();
		curl_multi_add_handle($this->mh, $this->ch);
		return true;
	}

	/**
	 * Returns true if an asynchronous authentication with Mojang is still pending.
	 *
	 * @return boolean
	 * @see ClientConnection::handleAuthentication
	 */
	public function isAuthenticationPending()
	{
		return $this->mh !== null;
	}

	/**
	 * Checks if the asynchronous authentication with Mojang has finished.
	 * In case of an error, the client is disconnected and 0 is returned.
	 * If Mojang's response was not yet received, 1 is returned.
	 * If Mojang's response was received and the authentication was successful, an array such as this is returned:
	 * <pre>[
	 *   "id" => "11111111222233334444555555555555",
	 *   "name" => "Notch",
	 *   "properties" => [
	 *     [
	 *       "name" => "textures",
	 *       "value" => "<base64 string>",
	 *       "signature" => "<base64 string; signed data using Yggdrasil's private key>"
	 *     ]
	 *   ]
	 * ]</pre>
	 *
	 * @return integer|array
	 * @see ClientConnection::isAuthenticationPending
	 */
	public function handleAuthentication()
	{
		$active = 0;
		curl_multi_exec($this->mh, $active);
		if($active > 0)
		{
			return 1;
		}
		$json = json_decode(curl_multi_getcontent($this->ch), true);
		curl_multi_remove_handle($this->mh, $this->ch);
		curl_multi_close($this->mh);
		curl_close($this->ch);
		if(!$json || empty($json["id"]) || @$json["name"] !== $this->username)
		{
			$this->disconnect(["text" => "Failed to authenticate against session server."]);
			return 0;
		}
		return $json;
	}

	/**
	 * Sets the compression threshold and finishes the login.
	 *
	 * @param UUID $uuid The UUID of the client.
	 * @param Counter $eidCounter The server's Counter to assign an entity ID to the client.
	 * @param integer $compression_threshold Use -1 to disable compression.
	 * @return ClientConnection $this
	 * @throws IOException
	 */
	public function finishLogin(UUID $uuid, Counter $eidCounter, int $compression_threshold = 256)
	{
		if($this->state == 2)
		{
			if($compression_threshold > -1 || $this->protocol_version < 48)
			{
				$this->write_buffer = Connection::varInt(0x03);
				$this->writeVarInt($compression_threshold);
				$this->send();
			}
			$this->compression_threshold = $compression_threshold;
			$this->write_buffer = Connection::varInt(0x02);
			$this->writeString(($this->uuid = $uuid)->toString(true));
			$this->writeString($this->username);
			$this->send();
			$this->eid = $eidCounter->next();
			$this->state = 3;
		}
		return $this;
	}

	/**
	 * Disconnects the client with a reason.
	 *
	 * @param array|string $reason The reason of the disconnect; chat object.
	 */
	public function disconnect($reason = [])
	{
		if($reason && $this->state > 1)
		{
			try
			{
				if($this->state == 2) // Login
				{
					$this->write_buffer = Connection::varInt(0x00);
				}
				else // Play
				{
					$this->startPacket("disconnect");
				}
				$this->writeChat($reason);
				$this->send();
			}
			catch(IOException $ignored)
			{
			}
		}
		$this->close();
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 *
	 * @param string|integer $packet The name or ID of the new packet.
	 * @return Connection $this
	 * @throws DomainException
	 * @throws InvalidArgumentException
	 */
	public function startPacket($packet)
	{
		if(gettype($packet) == "string")
		{
			$packetId = ClientboundPacket::get($packet);
			if(!$packetId)
			{
				throw new DomainException("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		return parent::startPacket($packet);
	}

	/**
	 * Sends the client their abilities.
	 *
	 * @return ClientConnection $this
	 * @throws IOException
	 * @see ClientConnection::$invulnerable
	 * @see ClientConnection::$flying
	 * @see ClientConnection::$can_fly
	 * @see ClientConnection::$instant_breaking
	 * @see ClientConnection::$fly_speed
	 * @see ClientConnection::$walk_speed
	 */
	public function sendAbilities()
	{
		$packet = new ClientboundAbilitiesPacket();
		$packet->invulnerable = $this->invulnerable;
		$packet->flying = $this->flying;
		$packet->can_fly = $this->can_fly;
		$packet->instant_breaking = $this->instant_breaking;
		$packet->fly_speed = $this->fly_speed;
		$packet->walk_speed = $this->walk_speed;
		$packet->send($this);
		return $this;
	}

	/**
	 * Sets the client's abilities according to the given gamemode.
	 *
	 * @param integer $gamemode
	 * @return ClientConnection $this
	 * @see ClientConnection::sendAbilities
	 * @see ClientConnection::setGamemode
	 */
	public function setAbilities(int $gamemode)
	{
		$this->instant_breaking = ($gamemode == Gamemode::CREATIVE);
		$this->flying = ($gamemode == Gamemode::SPECTATOR);
		if($gamemode == Gamemode::CREATIVE || $gamemode == Gamemode::SPECTATOR)
		{
			$this->invulnerable = true;
			$this->can_fly = true;
		}
		else
		{
			$this->invulnerable = false;
			$this->can_fly = false;
		}
		return $this;
	}

	/**
	 * Sets the client's gamemode and adjusts their abilities accordingly.
	 *
	 * @param integer $gamemode
	 * @return ClientConnection $this
	 * @throws DomainException
	 * @throws IOException
	 * @see Gamemode
	 */
	public function setGamemode(int $gamemode)
	{
		if(!Gamemode::validateValue($gamemode))
		{
			throw new DomainException("Invalid gamemode: ".$gamemode);
		}
		$this->gamemode = $gamemode;
		$this->startPacket("change_game_state");
		$this->writeByte(3);
		$this->writeFloat($gamemode);
		$this->send();
		$this->setAbilities($gamemode);
		$this->sendAbilities();
		return $this;
	}
}
