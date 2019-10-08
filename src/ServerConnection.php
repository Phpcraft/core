<?php
namespace Phpcraft;
use DomainException;
use hellsh\UUID;
use Phpcraft\
{Exception\IOException, Packet\ServerboundPacket};
/** A client-to-server connection. */
class ServerConnection extends Connection
{
	public $username;
	public $uuid;

	/**
	 * @param resource $stream A stream created by fsockopen.
	 * @param int $protocol_version
	 */
	function __construct($stream, int $protocol_version)
	{
		parent::__construct($protocol_version, $stream);
	}

	/**
	 * Sends a handshake to the server.
	 * If $next_state is 2, you should call ServerConnection::login() after this, even when joining an offline mode server.
	 *
	 * @param string $server_name
	 * @param int $server_port
	 * @param int $next_state Use 1 for status, or 2 for login to play.
	 * @param array<string> $join_specs Additional data to provide, e.g. "FML" is in this array for Forge clients.
	 * @return ServerConnection $this
	 * @throws IOException
	 */
	function sendHandshake(string $server_name, int $server_port, int $next_state, array $join_specs = []): ServerConnection
	{
		$this->writeVarInt(0x00);
		$this->writeVarInt($this->protocol_version);
		if($join_specs)
		{
			$server_name .= "\0".implode("\0", $join_specs);
		}
		$this->writeString($server_name);
		$this->writeUnsignedShort($server_port);
		$this->writeVarInt($this->state = $next_state);
		$this->send();
		return $this;
	}

	/**
	 * Logs in to the server using the given account.
	 * This has to be called even when joining an offline mode server.
	 *
	 * @param Account $account
	 * @param array<string,string>|null $translations The translations array so translated messages look proper.
	 * @return string Error message. Empty on success.
	 * @throws IOException
	 */
	function login(Account $account, array $translations = null): string
	{
		$this->writeVarInt(0x00);
		$this->writeString($account->username);
		$this->send();
		do
		{
			$id = $this->readPacket();
			if($id === false)
			{
				return "Read timed out.";
			}
			if($id == 0x04) // Login Plugin Request
			{
				$this->writeVarInt(0x02); // Login Plugin Response
				$this->writeVarInt(gmp_intval($this->readVarInt()));
				echo "Login Plugin Request: ".$this->readString()."\n";
				$this->writeBoolean(false);
				$this->send();
			}
			else if($id == 0x03) // Set Compression
			{
				$this->compression_threshold = gmp_intval($this->readVarInt());
			}
			else if($id == 0x02) // Login Success
			{
				$this->uuid = new UUID($this->readString(36, 36));
				$this->username = $this->readString(16, 3);
				$this->state = 3;
				break;
			}
			else if($id == 0x01) // Encryption Request
			{
				if(!$account->isOnline())
				{
					return "The server is in online mode.";
				}
				$server_id = $this->readString(20);
				$public_key = $this->readString();
				$verify_token = $this->readString();
				$shared_secret = "";
				for($i = 0; $i < 16; $i++)
				{
					$shared_secret .= chr(rand(0, 255));
				}
				if(Phpcraft::httpPOST("https://sessionserver.mojang.com/session/minecraft/join", [
						"accessToken" => $account->accessToken,
						"selectedProfile" => $account->profileId,
						"serverId" => Phpcraft::sha1($server_id.$shared_secret.$public_key)
					]) === false)
				{
					return "The session servers are down for maintenance.";
				}
				$public_key = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".base64_encode($public_key)."\n-----END PUBLIC KEY-----");
				$this->writeVarInt(0x01); // Encryption Response
				$crypted = "";
				openssl_public_encrypt($shared_secret, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				openssl_public_encrypt($verify_token, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				$this->send();
				$opts = [
					"mode" => "cfb",
					"iv" => $shared_secret,
					"key" => $shared_secret
				];
				stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
				stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
			}
			else if($id == 0x00) // Disconnect
			{
				return Phpcraft::chatToText(json_decode($this->readString(), true), 0, $translations);
			}
			else
			{
				return "Unexpected response: {$id} ".bin2hex($this->read_buffer);
			}
		}
		while(true);
		return "";
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 *
	 * @param string|integer $packet The name or ID of the new packet.
	 * @return Connection $this
	 */
	function startPacket($packet): Connection
	{
		if(gettype($packet) == "string")
		{
			$packetId = ServerboundPacket::get($packet);
			if(!$packetId)
			{
				throw new DomainException("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		return parent::startPacket($packet);
	}
}
