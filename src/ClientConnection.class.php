<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/Connection.class.php";
/** A server-to-client connection. */
class ClientConnection extends Connection
{
	/**
	 * The constructor.
	 * The handshake will be read and the connection will be closed when an error occurs.
	 * After this, you should check Connection::isOpen() and then Connection::getState() to see if the client wants to get the status (1) or login to play (2).
	 * @param resource $stream
	 */
	function __construct($stream)
	{
		parent::__construct(-1, $stream);
		stream_set_timeout($this->stream, 0, 10000);
		stream_set_blocking($this->stream, true);
		if($this->readPacket() === 0x00)
		{
			$this->protocol_version = $this->readVarInt();
			$this->readString(); // hostname/ip
			$this->ignoreBytes(2); // port
			$this->state = $this->readVarInt();
			if($this->state == 1 || $this->state == 2)
			{
				if($this->state != 2 || Phpcraft::isProtocolVersionSupported($this->protocol_version))
				{
					stream_set_timeout($this->stream, ini_get("default_socket_timeout"));
					stream_set_blocking($this->stream, false);
				}
				else
				{
					$this->writeVarInt(0x00);
					$this->writeString('{"text":"You\'re not using a compatible version."}');
					$this->send();
					$this->close();
				}
			}
			else
			{
				$this->close();
			}
		}
		else
		{
			$this->close();
		}
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
			$this->writeString("MATE"); // Verify Token
			$this->send();
		}
		return $this;
	}

	/**
	 * Reads an encryption response packet's data, authenticates with Mojang, sets the compression threshold, and finishes login.
	 * If there is an error, the client is disconnected and false is returned.
	 * On success, an array looking like this is returned:
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
	 * @param string $name The name the client presented in the Login Start packet.
	 * @param string $private_key Your OpenSSL private key resource.
	 * @return mixed
	 */
	function handleEncryptionResponse($name, $private_key)
	{
		openssl_private_decrypt($this->readString(), $shared_secret, $private_key, OPENSSL_PKCS1_PADDING);
		openssl_private_decrypt($this->readString(), $verify_token, $private_key, OPENSSL_PKCS1_PADDING);
		if($verify_token !== "MATE")
		{
			$this->close();
			return false;
		}
		$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
		stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
		stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
		$json = @json_decode(@file_get_contents("https://sessionserver.mojang.com/session/minecraft/hasJoined?username={$name}&serverId=".Phpcraft::sha1($shared_secret.base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24))))), true);
		if(!$json || empty($json["id"]) || empty($json["name"]) || $json["name"] != $name)
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
	 * @param string $uuid The client's UUID as a string with hypens.
	 * @param string $name The name the client presented in the Login Start packet.
	 * @param integer $compression_threshold Use -1 to disable compression.
	 * @return ClientConnection $this
	 * @see Phpcraft::generateUUIDv4()
	 * @see Phpcraft::addHypensToUUID()
	 */
	function finishLogin($uuid, $name, $compression_threshold = 256)
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
			$this->writeString($uuid);
			$this->writeString($name);
			$this->send();
			$this->state = 3;
		}
		return $this;
	}

	/**
	 * Disconnects the client with a reason.
	 * @param array $reason The reason of the disconnect; chat object.
	 * @return void
	 */
	function disconnect($reason = [])
	{
		if($reason)
		{
			if($this->state == 2)
			{

				$this->writeVarInt(0x00);
				$this->writeString(json_encode($reason));
				$this->send();
			}
			else
			{
				(new DisconnectPacket($reason))->send($this);
			}
		}
		$this->close();
	}
}