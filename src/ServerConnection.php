<?php
namespace Phpcraft;
use DomainException;
use hellsh\UUID;
use Phpcraft\
{Command\CommandSender, Exception\IOException, Packet\ServerboundPacketId};
/** A client-to-server connection. */
class ServerConnection extends Connection implements CommandSender
{
	/**
	 * Modern list ping. Legacy if that fails.
	 */
	const METHOD_ALL = 0;
	const METHOD_MODERN = 1;
	const METHOD_LEGACY = 2;
	/**
	 * The username assigned to us by the server after login. Null before that.
	 *
	 * @var string|null $username
	 */
	public $username;
	/**
	 * The UUID assigned to us by the server after login. Null before that.
	 *
	 * @var UUID|null $uuid
	 */
	public $uuid;
	/**
	 * Our position on the server.
	 *
	 * @var Point3D $pos
	 */
	public $pos;

	/**
	 * @param resource $stream A stream created by fsockopen.
	 * @param int $protocol_version
	 */
	function __construct($stream, int $protocol_version)
	{
		parent::__construct($protocol_version, $stream);
	}

	/**
	 * @param string $hostname
	 * @param int $port
	 * @param null|int $protocol_version If null, server will be list pinged to get a protocol version.
	 * @param null|int $handshake_next_state If null, no handshake will be sent. Otherway, this may be Connection::STATE_STATUS for list ping or Connection::STATE_LOGIN for login to play.
	 * @param float $timeout Timeout in seconds. Note that in case of $protocol_version === null, two connections will be opened using the same timeout.
	 * @return ServerConnection
	 * @throws IOException
	 * @since 0.5.19
	 */
	static function connect(string $hostname, int $port, ?int $protocol_version = null, ?int $handshake_next_state = null, float $timeout = 3.000): ServerConnection
	{
		if($protocol_version === null)
		{
			$protocol_version = self::negotiateProtocolVersion($hostname, $port, $timeout);
		}
		if(($stream = @fsockopen($hostname, $port, $errno, $errstr, $timeout)) === false)
		{
			throw new IOException("Error opening socket: $errstr ($errno)");
		}
		$con = new ServerConnection($stream, $protocol_version);
		if($handshake_next_state !== null)
		{
			$con->sendHandshake($hostname, $port, $handshake_next_state);
		}
		return $con;
	}

	/**
	 * @param string $address
	 * @param null|int $protocol_version If null, server will be list pinged to get a protocol version.
	 * @param null|int $handshake_next_state If null, no handshake will be sent. Otherway, this may be Connection::STATE_STATUS for list ping or Connection::STATE_LOGIN for login to play.
	 * @param float $timeout Timeout in seconds. Note that in case of $protocol_version === null, two connections will be opened using the same timeout.
	 * @return ServerConnection
	 * @throws IOException
	 * @since 0.5.19
	 */
	static function toAddress(string $address, ?int $protocol_version = null, ?int $handshake_next_state = null, float $timeout = 3.000): ServerConnection
	{
		$server = self::resolveAddress($address);
		return self::connect($server["hostname"], $server["port"], $protocol_version, $handshake_next_state, $timeout);
	}

	/**
	 * @param string $hostname
	 * @param int $port
	 * @param float $timeout Timeout in seconds.
	 * @return int
	 * @throws IOException
	 */
	static function negotiateProtocolVersion(string $hostname, int $port, float $timeout = 3.000): int
	{
		$info = self::getStatus($hostname, $port, $timeout, self::METHOD_MODERN);
		if(empty($info))
		{
			throw new IOException("Failed to connect to server");
		}
		if(!is_int(@$info["version"]["protocol"]))
		{
			throw new IOException("Server returned an invalid data in response to list ping: ".json_encode($info));
		}
		return $info["version"]["protocol"];
	}

	/**;
	 * Resolves the given address.
	 *
	 * @param string $address The server address, e.g. localhost
	 * @return array{hostname:string,port:int} An array containing string "hostname" and int "port".
	 * @since 0.5.19
	 */
	static function resolveAddress(string $address): array
	{
		if(($i = strpos($address, ":")) !== false)
		{
			return [
				"hostname" => substr($address, 0, $i),
				"port" => intval(substr($address, $i + 1))
			];
		}
		if(ip2long($address) === false && $res = @dns_get_record("_minecraft._tcp.{$address}", DNS_SRV))
		{
			$i = array_rand($res);
			return [
				"hostname" => $res[$i]["target"],
				"port" => $res[$i]["port"]
			];
		}
		return [
			"hostname" => $address,
			"port" => 25565
		];
	}

	/**
	 * Returns the server list ping as multi-dimensional array with the addition of the "ping" value which is in seconds. In an error case, an empty array is returned.
	 * Here's an example:
	 * <pre>[
	 *   "version" => [
	 *     "name" => "1.12.2",
	 *     "protocol" => 340
	 *   ],
	 *   "players" => [
	 *     "online" => 1,
	 *     "max" => 20,
	 *     "sample" => [
	 *       [
	 *         "name" => "timmyRS",
	 *         "id" => "e0603b59-2edc-45f7-acc7-b0cccd6656e1"
	 *       ]
	 *     ]
	 *   ],
	 *   "description" => [
	 *     "text" => "A Minecraft Server"
	 *   ],
	 *   "favicon" => "data:image/png;base64,&lt;data&gt;",
	 *   "ping" => 0.068003177642822
	 * ]</pre>
	 * Note that a server might not present all of these values, so always check with `isset` or `array_key_exists` first.
	 * `description` should always be a valid chat component.
	 *
	 * @param string $hostname
	 * @param int $port
	 * @param float $timeout The amount of seconds to wait for a response with each method.
	 * @param int $method
	 * @return array
	 * @throws IOException
	 * @since 0.5.19
	 */
	static function getStatus(string $hostname, int $port = 25565, float $timeout = 3.000, int $method = ServerConnection::METHOD_ALL): array
	{
		if($method != ServerConnection::METHOD_LEGACY)
		{
			if($stream = @fsockopen($hostname, $port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream, Versions::protocol(false)[0]);
				$start = microtime(true);
				$con->sendHandshake($hostname, $port, Connection::STATE_STATUS);
				$con->writeVarInt(0x00); // Status Request
				$con->send();
				if($con->readPacket($timeout) === 0x00)
				{
					$json = json_decode($con->readString(), true);
					$json["ping"] = microtime(true) - $start;
					$con->close();
					return $json;
				}
				$con->close();
			}
		}
		if($method != ServerConnection::METHOD_MODERN)
		{
			if($stream = @fsockopen($hostname, $port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream, 73);
				$start = microtime(true);
				$con->writeByte(0xFE);
				$con->writeByte(0x01);
				$con->writeByte(0xFA);
				$con->writeShort(11);
				$con->writeRaw(mb_convert_encoding("MC|PingHost", "utf-16be"));
				$utf_18be_hostname = mb_convert_encoding($hostname, "utf-16be");
				$con->writeShort(strlen($utf_18be_hostname) + 7);
				$con->writeByte($con->protocol_version);
				$con->writeShort(strlen($hostname));
				$con->writeRaw($utf_18be_hostname);
				$con->writeInt($port);
				$con->send(true);
				if($con->readRawPacket($timeout))
				{
					$arr = explode("\x00\x00", substr($con->read_buffer, 9));
					$con->close();
					return [
						"version" => [
							"name" => mb_convert_encoding($arr[1], mb_internal_encoding(), "utf-16be")
						],
						"players" => [
							"max" => intval(mb_convert_encoding($arr[4], mb_internal_encoding(), "utf-16be")),
							"online" => intval(mb_convert_encoding($arr[3], mb_internal_encoding(), "utf-16be"))
						],
						"description" => ChatComponent::text(mb_convert_encoding($arr[2], mb_internal_encoding(), "utf-16be"))
													  ->toArray(),
						"ping" => (microtime(true) - $start)
					];
				}
				$con->close();
			}
		}
		return [];
	}

	/**
	 * Sends a handshake to the server.
	 * If $next_state is 2, you should call ServerConnection::login() after this, even when joining an offline mode server.
	 *
	 * @param string $hostname
	 * @param int $port
	 * @param int $next_state May be Connection::STATE_STATUS for list ping or Connection::STATE_LOGIN for login to play.
	 * @param array<string> $join_specs Additional data to provide, e.g. "FML" is in this array for Forge clients.
	 * @return ServerConnection $this
	 * @throws IOException
	 */
	function sendHandshake(string $hostname, int $port, int $next_state, array $join_specs = []): ServerConnection
	{
		$this->writeVarInt(0x00);
		$this->writeVarInt($this->protocol_version);
		if($join_specs)
		{
			$hostname .= "\0".join("\0", $join_specs);
		}
		$this->writeString($hostname);
		$this->writeUnsignedShort($port);
		$this->writeVarInt($this->state = $next_state);
		$this->send();
		return $this;
	}

	/**
	 * Logs in to the server using the given account.
	 * This has to be called even when joining an offline mode server.
	 *
	 * @param Account $account
	 * @return string|null The error message or null on success.
	 * @throws IOException
	 */
	function login(Account $account): ?string
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
				$this->uuid = ($this->protocol_version >= 707 ? $this->readUUID() : new UUID($this->readString(36, 36)));
				$this->username = $this->readString(16, 3);
				$this->state = self::STATE_PLAY;
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
				return $this->readChat()
							->toString();
			}
			else
			{
				return "Unexpected packet {$id}: ".Phpcraft::binaryStringToHex($this->read_buffer);
			}
		}
		while(true);
		return null;
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
			$packetId = ServerboundPacketId::get($packet);
			if(!$packetId)
			{
				throw new DomainException("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		return parent::startPacket($packet);
	}

	/**
	 * @return string
	 */
	function getName(): string
	{
		return $this->username ?? "Client";
	}

	/**
	 * Prints a message to the console.
	 * Available in accordance with the CommandSender interface.
	 *
	 * @param array|string|null|ChatComponent $message
	 * @return void
	 */
	function sendMessage($message): void
	{
		echo ChatComponent::cast($message)
						  ->toString(ChatComponent::FORMAT_ANSI)."\n";
	}

	/**
	 * Prints a message to the console.
	 * Available in accordance with the CommandSender interface.
	 *
	 * @param array|string|null|ChatComponent $message
	 * @param string $permission
	 * @return void
	 */
	function sendAdminBroadcast($message, string $permission = "everything"): void
	{
		echo ChatComponent::cast($message)
						  ->toString(ChatComponent::FORMAT_ANSI)."\n";
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	function hasPermission(string $permission): bool
	{
		return true;
	}

	/**
	 * @return bool
	 */
	function hasPosition(): bool
	{
		return true;
	}

	/**
	 * @return Point3D
	 */
	function getPosition(): Point3D
	{
		return $this->pos;
	}

	/**
	 * Available in accordance with the CommandSender interface.
	 *
	 * @return bool false
	 */
	function hasServer(): bool
	{
		return false;
	}

	/**
	 * Available in accordance with the CommandSender interface.
	 *
	 * @return Server|null null
	 */
	function getServer(): ?Server
	{
		return null;
	}
}
