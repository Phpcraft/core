<?php
namespace Phpcraft;
use BadMethodCallException;
use DomainException;
use GMP;
use hellsh\UUID;
use pas\pas;
use Phpcraft\
{Command\ServerCommandSender, Entity\Player, Enum\ChatPosition, Enum\Gamemode, Exception\IOException, Packet\ClientboundAbilitiesPacket, Packet\ClientboundPacketId};
/** A server-to-client connection. */
class ClientConnection extends Connection implements ServerCommandSender
{
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
	 * @var int $hostport
	 */
	public $hostport;
	/**
	 * Additional data the client had provided in the handshake.
	 * E.g., "FML" is in this array for Forge clients.
	 *
	 * @var array<string> $join_specs
	 */
	public $join_specs = [];
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
	 * @var ClientConfiguration $config
	 */
	public $config;
	/**
	 * This variable is for servers to keep track of when to send the next keep alive packet to clients.
	 *
	 * @var float $next_heartbeat
	 */
	public $next_heartbeat = 0;
	/**
	 * This variable is for servers to keep track of how long clients have to answer keep alive packets.
	 *
	 * @var float $disconnect_after
	 */
	public $disconnect_after = 0;
	/**
	 * Used by proxy servers to store the downstream connection instance.
	 *
	 * @var ServerConnection $downstream
	 */
	public $downstream;
	/**
	 * Used by proxy servers to store if the client is incompatible with the downstream server and needs packets to be converted.
	 *
	 * @var bool $convert_packets
	 */
	public $convert_packets;
	/**
	 * The client's entity ID.
	 *
	 * @var GMP $eid
	 */
	public $eid;
	/**
	 * The downstream entity ID. Used only on proxy servers.
	 *
	 * @var GMP $downstream_eid
	 */
	public $downstream_eid;
	/**
	 * The client's position.
	 *
	 * @var Point3D $pos
	 */
	public $pos;
	/**
	 * @var int|null $chunk_x
	 */
	public $chunk_x;
	/**
	 * @var int|null $chunk_z
	 */
	public $chunk_z;
	/**
	 * The client's rotation on the X axis, 0 to 359.9.
	 *
	 * @var float $yaw
	 */
	public $yaw = 0;
	/**
	 * The client's rotation on the Y axis, -90 to 90.
	 *
	 * @var float $pitch
	 */
	public $pitch = 0;
	/**
	 * @var Counter $tpidCounter
	 */
	public $tpidCounter;
	/**
	 * @var float $tp_confirm_deadline
	 */
	public $tp_confirm_deadline = 0;
	/**
	 * @var boolean $on_ground
	 * @see ServerOnGroundChangeEvent
	 */
	public $on_ground = false;
	/**
	 * @var int $render_distance
	 */
	public $render_distance = 8;
	/**
	 * A string array of chunks the client has received.
	 *
	 * @var array<string> $chunks
	 */
	public $chunks = [];
	/**
	 * @var int $gamemode
	 */
	public $gamemode = Gamemode::SURVIVAL;
	/**
	 * @var Player|null $entityMetadata
	 * @todo Create sendMetadata method
	 */
	public $entityMetadata;
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
	 * @param Server|null $server
	 */
	function __construct($stream, ?Server &$server = null)
	{
		parent::__construct(-1, $stream);
		$this->tpidCounter = new Counter();
		if($server)
		{
			$this->config = new ClientConfiguration($server, $this);
		}
	}

	/**
	 * Deals with the first packet the client has sent.
	 * This function deals with the handshake or legacy list ping packet.
	 * Errors will cause the connection to be closed.
	 *
	 * @return int Status: 0 = The client is yet to present an initial packet. 1 = Handshake was successfully read; use Connection::$state to see if the client wants to get the status (1) or login to play (2). 2 = A legacy list ping packet has been received. 3 = An error occured and the connection has been closed.
	 */
	function handleInitialPacket(): int
	{
		try
		{
			if($this->readRawPacket(0, 1))
			{
				$packet_length = $this->readUnsignedByte();
				if($packet_length == 0xFE)
				{
					if($this->readRawPacket(0) && $this->readUnsignedByte() == 0x01 && $this->readUnsignedByte() == 0xFA && $this->readShort() == 11 && $this->readRaw(22) == mb_convert_encoding("MC|PingHost", "utf-16be"))
					{
						$this->ignoreBytes(2);
						$this->protocol_version = $this->readByte();
						$this->setHostname(mb_convert_encoding($this->readRaw($this->readShort() * 2), "utf-8", "utf-16be"));
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
						$this->setHostname($this->readString());
						$this->hostport = $this->readUnsignedShort();
						$this->state = gmp_intval($this->readVarInt());
						if($this->state == self::STATE_STATUS || $this->state == self::STATE_LOGIN)
						{
							return 1;
						}
						throw new IOException("Invalid state: ".$this->state);
					}
				}
			}
		}
		catch(IOException $e)
		{
			$this->disconnect($e->getMessage());
			return 3;
		}
		return 0;
	}

	private function setHostname(string $hostname): void
	{
		$arr = explode("\0", $hostname);
		$this->hostname = $arr[0];
		$this->join_specs = array_slice($arr, 1);
	}

	/**
	 * Disconnects the client with an optional reason.
	 *
	 * @param array|string|null|ChatComponent $reason The reason for the disconnect.
	 * @return void
	 */
	function disconnect($reason = null): void
	{
		if($this->state == self::STATE_PLAY || $this->state == self::STATE_LOGIN)
		{
			try
			{
				if($this->state == self::STATE_PLAY)
				{
					$this->startPacket("disconnect");
				}
				else // STATE_LOGIN
				{
					$this->write_buffer = Connection::varInt(0x00);
				}
				$this->writeChat(ChatComponent::cast($reason));
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
	 */
	function startPacket($packet): Connection
	{
		if(gettype($packet) == "string")
		{
			$packetId = ClientboundPacketId::get($packet);
			if(!$packetId)
			{
				throw new DomainException("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		return parent::startPacket($packet);
	}

	/**
	 * Returns the host the client had connected to, e.g. localhost:25565.
	 * Note that SRV records are pre-connection redirects, so if _minecraft._tcp.example.com points to mc.example.com which is an A or AAAA record, this will return mc.example.com:25565.
	 *
	 * @return string
	 */
	function getHost(): string
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
	function sendEncryptionRequest($private_key): ClientConnection
	{
		if($this->state == self::STATE_LOGIN)
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
	 * In case of an error, the client is disconnected and false is returned.
	 * Should the authentication with Mojang finish successfully, the callback is called with an array like this as argument:
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
	 * @param resource $private_key Your OpenSSL private key resource.
	 * @param callable $callback
	 * @return boolean
	 * @throws IOException
	 */
	function handleEncryptionResponse($private_key, callable $callback): bool
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
		$ch = curl_init("https://sessionserver.mojang.com/session/minecraft/hasJoined?username=".$this->username."&serverId=".Phpcraft::sha1($shared_secret.base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24))))."&ip=".$this->getRemoteAddress());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if(Phpcraft::isWindows())
		{
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__."/cacert.pem");
		}
		pas::curl_exec($ch, function($res) use (&$ch, &$callback)
		{
			curl_close($ch);
			$json = json_decode($res, true);
			if(!$json || empty($json["id"]) || @$json["name"] !== $this->username)
			{
				$this->disconnect("Failed to authenticate against the session server.");
			}
			$callback($json);
		});
		return true;
	}

	/**
	 * Sets the compression threshold and finishes the login.
	 *
	 * @param UUID $uuid The UUID of the client.
	 * @param Counter $eidCounter The server's Counter to assign an entity ID to the client.
	 * @param int $compression_threshold The amount of bytes a packet needs to have before it is compressed. Use -1 to disable compression. Compression will always be disabled on loopback connections.
	 * @return ClientConnection $this
	 * @throws IOException
	 */
	function finishLogin(UUID $uuid, Counter $eidCounter, int $compression_threshold = 256): ClientConnection
	{
		if($this->state != self::STATE_LOGIN)
		{
			throw new BadMethodCallException("Call to finishLogin on Connection in state ".$this->state);
		}
		if($compression_threshold > -1 && in_array($this->getRemoteAddress(), [
				"127.0.0.1",
				"::1"
			]))
		{
			$compression_threshold = -1;
		}
		if($compression_threshold > -1 || $this->protocol_version < 48)
		{
			$this->write_buffer = Connection::varInt(0x03);
			$this->writeVarInt($compression_threshold);
			$this->send();
		}
		$this->compression_threshold = $compression_threshold;
		$this->write_buffer = Connection::varInt(0x02); // Login Success
		$this->writeString(($this->uuid = $uuid)->toString(true));
		$this->writeString($this->username);
		$this->send();
		$this->eid = $eidCounter->next();
		$this->entityMetadata = new Player();
		$this->state = self::STATE_PLAY;
		if($this->config && $this->config->server->persist_configs)
		{
			$this->config->setFile("config/player_data/".$this->uuid->toString(false).".json");
		}
		return $this;
	}

	/**
	 * Teleports the client to the given position, and optionally, changes their rotation.
	 *
	 * @param Point3D $pos
	 * @param float|null $yaw
	 * @param float|null $pitch
	 * @return ClientConnection $this
	 * @throws IOException
	 */
	function teleport(Point3D $pos, ?float $yaw = null, ?float $pitch = null): ClientConnection
	{
		$this->pos = $pos;
		$this->chunk_x = ceil($pos->x / 16);
		$this->chunk_z = ceil($pos->x / 16);
		$flags = 0;
		if($yaw === null)
		{
			$flags |= 0x10;
		}
		else
		{
			$this->yaw = $yaw;
		}
		if($pitch === null)
		{
			$flags |= 0x08;
		}
		else
		{
			$this->pitch = $pitch;
		}
		$this->startPacket("teleport");
		$this->writePrecisePosition($this->pos);
		$this->writeFloat($yaw ?? 0);
		$this->writeFloat($pitch ?? 0);
		$this->writeByte($flags);
		if($this->protocol_version > 47)
		{
			$this->writeVarInt($this->tpidCounter->next());
			$this->tp_confirm_deadline = microtime(true) + 3;
		}
		$this->send();
		return $this;
	}

	/**
	 * Changes the client's rotation.
	 *
	 * @param int $yaw
	 * @param int $pitch
	 * @return ClientConnection $this
	 * @throws IOException
	 */
	function rotate(int $yaw, int $pitch): ClientConnection
	{
		$this->startPacket("teleport");
		$this->writeDouble(0);
		$this->writeDouble(0);
		$this->writeDouble(0);
		$this->writeFloat($this->yaw = $yaw);
		$this->writeFloat($this->pitch = $pitch);
		$this->writeByte(0b111);
		if($this->protocol_version > 47)
		{
			$this->writeVarInt($this->tpidCounter->next());
			$this->tp_confirm_deadline = microtime(true) + 3;
		}
		$this->send();
		return $this;
	}

	function getEyePosition(): Point3D
	{
		return $this->pos->add(0, $this->entityMetadata->crouching ? 1.28 : 1.62, 0);
	}

	/**
	 * Returns a unit vector goin in the direction the client is looking.
	 *
	 * @return Point3D
	 */
	function getUnitVector(): Point3D
	{
		return $this->pos->getUnitVector($this->yaw, $this->pitch);
	}

	/**
	 * Sends a message to the client and "[{$this-&gt;username}: $message]" to the server console and players with the given permission.
	 *
	 * @param array|string|null|ChatComponent $message
	 * @param string $permission
	 * @return void
	 * @throws IOException
	 */
	function sendAdminBroadcast($message, string $permission = "everything"): void
	{
		$this->sendMessage($message);
		$message = ChatComponent::text("[{$this->username}: ")->gray()->add($message)->add("]");
		echo $message->toString(ChatComponent::FORMAT_ANSI)."\n";
		foreach($this->getServer()->clients as $con)
		{
			assert($con instanceof ClientConnection);
			try
			{
				if($con != $this && $con->state == self::STATE_PLAY && $con->hasPermission($permission))
				{
					$con->sendMessage($message);
				}
			}
			catch(IOException $e)
			{
			}
		}
	}

	/**
	 * Sends a chat message to the client.
	 *
	 * @param array|string|null|ChatComponent $message
	 * @param int $position
	 * @return void
	 * @throws IOException
	 */
	function sendMessage($message, int $position = ChatPosition::SYSTEM): void
	{
		$this->startPacket("clientbound_chat_message");
		$this->writeChat(ChatComponent::cast($message));
		$this->writeByte($position);
		$this->send();
	}

	function getServer(): Server
	{
		return $this->config->server;
	}

	function hasPermission(string $permission): bool
	{
		return $this->config->hasPermission($permission);
	}

	/**
	 * Sets the client's gamemode and adjusts their abilities accordingly.
	 *
	 * @param int $gamemode
	 * @return ClientConnection $this
	 * @throws IOException
	 * @see Gamemode
	 */
	function setGamemode(int $gamemode): ClientConnection
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
		$this->setAbilitiesFromGamemode($gamemode)
			 ->sendAbilities();
		return $this;
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
	function sendAbilities(): ClientConnection
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
	 * @param int $gamemode
	 * @return ClientConnection $this
	 * @see ClientConnection::sendAbilities
	 * @see ClientConnection::setGamemode
	 */
	function setAbilitiesFromGamemode(int $gamemode): ClientConnection
	{
		$this->instant_breaking = ($gamemode == Gamemode::CREATIVE);
		$this->flying = ($gamemode == Gamemode::SPECTATOR);
		$this->invulnerable = $this->can_fly = ($gamemode == Gamemode::CREATIVE || $gamemode == Gamemode::SPECTATOR);
		return $this;
	}

	function getName(): string
	{
		return $this->username;
	}

	function hasPosition(): bool
	{
		return $this->pos !== null;
	}

	function getPosition(): ?Point3D
	{
		return $this->pos;
	}
}
