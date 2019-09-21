<?php
namespace Phpcraft;
use hellsh\UUID;
use Phpcraft\
{Command\CommandSender, Enum\ChatPosition, Exception\IOException, Packet\KeepAliveRequestPacket, Packet\ServerboundPacket, Permission\Group};
use SplObjectStorage;
class Server implements CommandSender
{
	/**
	 * The stream the server listens for new connections on.
	 *
	 * @var resource $stream
	 */
	public $stream;
	/**
	 * A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 *
	 * @var resource $private_key
	 */
	public $private_key;
	/**
	 * A ClientConnection array of all clients that are connected to the server.
	 *
	 * @var SplObjectStorage $clients
	 * @see Server::getPlayers()
	 */
	public $clients;
	/**
	 * The counter used to assign entity IDs.
	 *
	 * @var Counter $eidCounter
	 */
	public $eidCounter;
	/**
	 * Set to true if you'd like every client's config to be persisted across connections from the same client.
	 *
	 * @var bool $persist_configs
	 */
	public $persist_configs = false;
	/**
	 * The amount of bytes at which a packet will be compressed. This applies bi-directionally, but the server decides on the value. Use -1 to disable compression.
	 *
	 * @var int $compression_threshold
	 */
	public $compression_threshold = 256;
	/**
	 * @var array $groups
	 */
	public $groups = [];
	/**
	 * The function called when a client has entered state 3 (playing) with the ClientConnection as argument.
	 *
	 * @see Server::handle()
	 * @var callable $join_function
	 */
	public $join_function = null;
	/**
	 * The function called when the server receives a packet from a client in state 3 (playing) unless it's a keep alive response with the ClientConnection and ServerboundPacket as arguments.
	 *
	 * @see Server::handle()
	 * @var callable $packet_function
	 */
	public $packet_function = null;
	/**
	 * The function called when a client's disconnected from the server with the ClientConnection as argument.
	 *
	 * @see Server::handle()
	 * @var callable $disconnect_function
	 */
	public $disconnect_function = null;
	/**
	 * The function called when to get the server's response to a list ping request with the ClientConnection as argument.
	 * See Phpcraft::getServerStatus for an example of all the data a server may respond with (excluding "ping").
	 * Additionally, if you set "no_ping", the client will show "(no connection)" where usually the ping in ms would be.
	 *
	 * @see Server::accept()
	 * @see Server::handle()
	 * @var callable $list_ping_function
	 */
	public $list_ping_function = null;

	/**
	 * @param resource $stream A stream created by stream_socket_server.
	 * @param resource $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 */
	function __construct($stream = null, $private_key = null)
	{
		if($stream)
		{
			stream_set_blocking($stream, false);
			$this->stream = $stream;
		}
		$this->private_key = $private_key;
		$this->clients = new SplObjectStorage();
		$this->eidCounter = new Counter();
		$this->list_ping_function = function(ClientConnection $con)
		{
			$players = [];
			foreach($this->clients as $client)
			{
				if($client->state == 3)
				{
					array_push($players, [
						"name" => $client->username,
						"id" => $client->uuid->toString(true)
					]);
				}
			}
			$versions = Versions::minecraftReleases(false);
			return [
				"version" => [
					"name" => "Phpcraft ".$versions[count($versions) - 1]." - ".$versions[0],
					"protocol" => (Versions::protocolSupported($con->protocol_version) ? $con->protocol_version : Versions::protocol(false)[0])
				],
				"players" => [
					"online" => count($players),
					"max" => count($players) + 1,
					"sample" => $players
				],
				"description" => [
					"text" => "A \\Phpcraft\\Server"
				]
			];
		};
	}

	function setGroups(array $groups): Server
	{
		$this->groups = [];
		foreach($groups as $name => $data)
		{
			$this->groups[$name] = new Group($this, $data);
		}
		if(!array_key_exists("default", $this->groups))
		{
			$this->groups["default"] = new Group($this, []);
		}
		return $this;
	}

	/**
	 * Gets a group by its name.
	 *
	 * @param string $name
	 * @return Group|null
	 */
	function getGroup(string $name)
	{
		return @$this->groups[$name];
	}

	/**
	 * Returns whether the server socket is open or not.
	 *
	 * @return boolean
	 */
	function isOpen(): bool
	{
		return $this->stream !== null;
	}

	/**
	 * Accepts new clients and processes each client's first packet.
	 *
	 * @return Server $this
	 */
	function accept(): Server
	{
		while(($stream = @stream_socket_accept($this->stream, 0)) !== false)
		{
			$con = null;
			try
			{
				$con = new ClientConnection($stream, $this);
				switch($con->handleInitialPacket())
				{
					case 1:
						if($con->state == 1)
						{
							$con->disconnect_after = microtime(true) + 10;
						}
						$this->clients->attach($con);
						break;
					case 2: // Legacy List Ping
						$json = ($this->list_ping_function)($con);
						if(!isset($json["players"]))
						{
							$json["players"] = [];
						}
						if(!isset($json["players"]["online"]))
						{
							$json["players"]["online"] = 0;
						}
						if(!isset($json["players"]["max"]))
						{
							$json["players"]["max"] = 0;
						}
						$data = "§1\x00127\x00".@$json["version"]["name"]."\x00".Phpcraft::chatToText(@$json["description"], 2)."\x00".$json["players"]["online"]."\x00".$json["players"]["max"];
						$con->writeByte(0xFF);
						$con->writeShort(mb_strlen($data));
						$con->writeRaw(mb_convert_encoding($data, "utf-16be"));
						$con->send(true);
						$con->close();
				}
			}
			catch(IOException $ignored)
			{
				if($con != null)
				{
					$con->close();
				}
			}
		}
		return $this;
	}

	/**
	 * Deals with all connected clients.
	 * This includes responding to status requests, dealing with keep alive packets, closing dead connections, and saving client configurations.
	 * This does not include implementing an entire server; that is what the packet_function is for.
	 *
	 * @return Server $this
	 */
	function handle(): Server
	{
		foreach($this->clients as $con)
		{
			assert($con instanceof ClientConnection);
			if($con->isOpen())
			{
				try
				{
					while(($packet_id = $con->readPacket(0)) !== false)
					{
						if($con->state == 3) // Playing
						{
							$packetId = ServerboundPacket::getById($packet_id, $con->protocol_version);
							if($packetId->name == "keep_alive_response")
							{
								$con->next_heartbeat = microtime(true) + 15;
								$con->disconnect_after = 0;
							}
							else if($this->packet_function)
							{
								($this->packet_function)($con, $packetId);
							}
						}
						else if($con->state == 2) // Login
						{
							if($packet_id == 0x00) // Login Start
							{
								$con->username = $con->readString();
								if(Phpcraft::validateName($con->username))
								{
									if($this->private_key)
									{
										$con->sendEncryptionRequest($this->private_key);
									}
									else
									{
										$con->finishLogin(UUID::v5("OfflinePlayer:".$con->username), $this->eidCounter, $this->compression_threshold);
										if($this->join_function)
										{
											($this->join_function)($con);
										}
										$con->next_heartbeat = microtime(true) + 15;
									}
								}
								else
								{
									$con->disconnect_after = 1;
									break;
								}
							}
							else if($packet_id == 0x01 && isset($con->username)) // Encryption Response
							{
								$con->handleEncryptionResponse($this->private_key);
							}
							else
							{
								$con->disconnect_after = 1;
								break;
							}
						}
						else // Can only be 1; Status
						{
							if($packet_id == 0x00)
							{
								$json = ($this->list_ping_function)($con);
								if($no_ping = isset($json["no_ping"]))
								{
									unset($json["no_ping"]);
								}
								$con->writeVarInt(0x00);
								$con->writeString(json_encode($json));
								$con->send();
								if($no_ping)
								{
									$con->disconnect_after = 1;
									break;
								}
							}
							else if($packet_id == 0x01)
							{
								$con->writeVarInt(0x01);
								$con->writeLong($con->readLong());
								$con->send();
								$con->disconnect_after = 1;
								break;
							}
						}
					}
					if($con->disconnect_after != 0 && $con->disconnect_after <= microtime(true))
					{
						$con->close();
						continue;
					}
					if($con->next_heartbeat != 0 && $con->next_heartbeat <= microtime(true))
					{
						(new KeepAliveRequestPacket(time()))->send($con);
						$con->next_heartbeat = 0;
						$con->disconnect_after = microtime(true) + 30;
					}
					if($con->state == 2 && $con->isAuthenticationPending())
					{
						$res = $con->handleAuthentication();
						if(is_array($res))
						{
							Phpcraft::$user_cache->set($res["id"], $con->username);
							$con->finishLogin(new UUID($res["id"]), $this->eidCounter, $this->compression_threshold);
							if($this->join_function)
							{
								($this->join_function)($con);
							}
							$con->next_heartbeat = microtime(true) + 15;
						}
					}
				}
				catch(IOException $e)
				{
					if($con->username)
					{
						echo "Disconnected ".$con->username.": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
					}
					$con->disconnect(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
				}
			}
			if(!$con->isOpen())
			{
				if($this->disconnect_function)
				{
					($this->disconnect_function)($con);
				}
				$this->clients->detach($con);
			}
		}
		Configuration::handleQueue(0.03);
		return $this;
	}

	/**
	 * Returns true if the server is in online mode.
	 *
	 * @return bool
	 */
	function isOnlineMode(): bool
	{
		return $this->private_key !== null;
	}

	/**
	 * Sends a message to all players (clients in playing state).
	 *
	 * @param string|array $message
	 * @param int $position
	 * @return Server $this
	 */
	function broadcast($message, int $position = ChatPosition::SYSTEM): Server
	{
		if(is_string($message))
		{
			$message = Phpcraft::textToChat($message);
		}
		foreach(self::getPlayers() as $con)
		{
			try
			{
				$con->sendMessage($message, $position);
			}
			catch(IOException $e)
			{
			}
		}
		return $this;
	}

	/**
	 * Returns all clients in state 3 (playing).
	 *
	 * @return ClientConnection[]
	 */
	function getPlayers(): array
	{
		$clients = [];
		foreach($this->clients as $client)
		{
			if($client->state == 3)
			{
				array_push($clients, $client);
			}
		}
		return $clients;
	}

	/**
	 * Gets the ClientConfiguration of a player who might be offline.
	 *
	 * @param string|UUID $name_or_uuid
	 * @return ClientConfiguration|null
	 */
	function getOfflinePlayer(string $name_or_uuid)
	{
		$player = $this->getPlayer($name_or_uuid);
		if($player !== null)
		{
			return $player->config;
		}
		if($name_or_uuid instanceof UUID)
		{
			return new ClientConfiguration($this, null, "config/player_data/".$name_or_uuid->toString(false).".json");
		}
		$name_or_uuid = strtolower($name_or_uuid);
		foreach(Phpcraft::$user_cache as $uuid => $_name)
		{
			if(strtolower($_name) == $name_or_uuid)
			{
				return new ClientConfiguration($this, null, "config/player_data/{$uuid}.json");
			}
		}
		return null;
	}

	/**
	 * Returns a client in state 3 (playing) with the given name or UUID, or null if not found.
	 *
	 * @param string|UUID $name_or_uuid
	 * @return ClientConnection|null
	 */
	function getPlayer($name_or_uuid)
	{
		foreach($this->clients as $client)
		{
			assert($client instanceof ClientConnection);
			if($client->state == 3 && ($client->username == $name_or_uuid || $client->uuid == $name_or_uuid))
			{
				return $client;
			}
		}
		return null;
	}

	function getName(): string
	{
		return "Server";
	}

	/**
	 * Prints a message to the console.
	 * Available in accordance with the CommandSender interface.
	 * If you want to print to console specifically, just use PHP's `echo`.
	 *
	 * @param array|string $message
	 * @return Server $this
	 */
	function sendMessage($message): Server
	{
		echo Phpcraft::chatToText($message, Phpcraft::FORMAT_ANSI)."\n\e[m";
		return $this;
	}

	/**
	 * @param array|string $message
	 */
	function sendAndPrintMessage($message)
	{
		echo Phpcraft::chatToText($message, Phpcraft::FORMAT_ANSI)."\n\e[m";
	}

	/**
	 * Closes all client connections and the server socket.
	 *
	 * @param array|string $reason The reason for closing the server; chat object.
	 */
	function close($reason = [])
	{
		fclose($this->stream);
		$this->stream = null;
		foreach($this->clients as $client)
		{
			$client->disconnect($reason);
		}
	}

	function hasPermission(string $permission): bool
	{
		return true;
	}

	function hasServer(): bool
	{
		return true;
	}

	function getServer(): Server
	{
		return $this;
	}

	function hasPosition(): bool
	{
		return false;
	}

	/**
	 * @return null
	 */
	function getPosition()
	{
		return null;
	}
}
