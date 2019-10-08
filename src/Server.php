<?php
namespace Phpcraft;
use Exception;
use hellsh\UUID;
use Phpcraft\
{Command\CommandSender, Enum\ChatPosition, Exception\IOException, Packet\KeepAliveRequestPacket, Packet\ServerboundPacket, Permission\Group};
use SplObjectStorage;
class Server implements CommandSender
{
	/**
	 * The streams the server listens for new connections on.
	 *
	 * @var array<resource> $streams
	 */
	public $streams;
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
	 * @var array<Group> $groups
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
	 * The function called when to get the server's response to a list ping request with the ClientConnection as argument or null if called internally to get list ping information, e.g. in a plugin.
	 * See Phpcraft::getServerStatus for an example of all the data a server may respond with (excluding "ping").
	 * Additionally, if you set "no_ping", the client will show "(no connection)" where usually the ping in ms would be.
	 *
	 * @see Server::accept()
	 * @see Server::handle()
	 * @var callable $list_ping_function
	 */
	public $list_ping_function = null;

	/**
	 * @param array<resource> $streams An array of streams created by stream_socket_server to listen for clients on.
	 * @param resource $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 */
	function __construct(array $streams = [], $private_key = null)
	{
		foreach($streams as $stream)
		{
			stream_set_blocking($stream, false);
		}
		$this->streams = $streams;
		$this->private_key = $private_key;
		$this->clients = new SplObjectStorage();
		$this->eidCounter = new Counter();
		$this->list_ping_function = function(ClientConnection $con = null)
		{
			$data = [
				"description" => [
					"text" => "A \\Phpcraft\\Server"
				]
			];
			if($con !== null)
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
				$data["players"] = [
					"online" => count($players),
					"max" => count($players) + 1,
					"sample" => $players
				];
				$versions = Versions::minecraftReleases(false);
				$data["version"] = [
					"name" => "Phpcraft ".$versions[count($versions) - 1]." - ".$versions[0],
					"protocol" => (Versions::protocolSupported($con->protocol_version) ? $con->protocol_version : Versions::protocol(false)[0])
				];
			}
			return $data;
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
	 * Returns true if the server has at least one socket to listen for new connections on.
	 *
	 * @return boolean
	 */
	function isOpen(): bool
	{
		return $this->streams !== [];
	}

	/**
	 * Returns the ports the server is listening on.
	 *
	 * @return int[]
	 */
	function getPorts(): array
	{
		$ports = [];
		foreach($this->streams as $stream)
		{
			$name = stream_socket_get_name($stream, false);
			array_push($ports, intval(substr($name, strpos($name, ":") + 1)));
		}
		return $ports;
	}

	/**
	 * Returns the "description" key from $this->list_ping_function's return array.
	 *
	 * @return array|string
	 * @see Server::$list_ping_function
	 */
	function getMotd()
	{
		return ($this->list_ping_function)()["description"];
	}

	/**
	 * Accepts new clients.
	 *
	 * @return Server $this
	 */
	function accept(): Server
	{
		foreach($this->streams as $stream)
		{
			while(($in = @stream_socket_accept($stream, 0)) !== false)
			{
				$con = new ClientConnection($in, $this);
				$con->disconnect_after = microtime(true) + 3;
				$this->clients->attach($con);
			}
		}
		return $this;
	}

	/**
	 * Deals with all connected clients.
	 * This includes dealing with handshakes, status requests, keep alive packets, closing dead connections, and saving client configurations.
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
					if($con->state == 0) // Handshaking
					{
						switch($con->handleInitialPacket())
						{
							case 1:
								$con->disconnect_after = microtime(true) + 3;
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
					else
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
											$con->disconnect_after = microtime(true) + 10;
											$con->sendEncryptionRequest($this->private_key);
										}
										else
										{
											$con->disconnect_after = 0;
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
									$con->disconnect_after = 0;
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
				catch(Exception $e)
				{
					if($con->username)
					{
						echo "Disconnected ".$con->username.": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
					}
					$con->disconnect(get_class($e).": ".$e->getMessage());
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
	 * @param array|string $message
	 * @param int $position
	 * @return Server $this
	 */
	function broadcast($message, int $position = ChatPosition::SYSTEM): Server
	{
		if(!is_array($message))
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
	 * Sends a message to the server console and all players with the given permission, e.g. "everything" for administrators.
	 *
	 * @param array|string $message
	 * @param string $permission
	 * @return Server
	 */
	function adminBroadcast($message, string $permission = "everything"): Server
	{
		if(!is_array($message))
		{
			$message = Phpcraft::textToChat($message);
		}
		echo Phpcraft::chatToText($message, Phpcraft::FORMAT_ANSI)."\n";
		return $this->permissionBroadcast($permission, $message);
	}

	/**
	 * Sends a message to all clients in playing state with the given permission.
	 *
	 * @param string $permission
	 * @param array|string $message
	 * @param int $position
	 * @return Server $this
	 */
	function permissionBroadcast(string $permission, $message, int $position = ChatPosition::SYSTEM): Server
	{
		if(!is_array($message))
		{
			$message = Phpcraft::textToChat($message);
		}
		foreach($this->clients as $con)
		{
			assert($con instanceof ClientConnection);
			try
			{
				if($con->state == 3 && $con->hasPermission($permission))
				{
					$con->sendMessage($message, $position);
				}
			}
			catch(IOException $e)
			{
			}
		}
		return $this;
	}

	/**
	 * Sends a message to the server console and "[Server: $message]" to all players with the given permission.
	 *
	 * @param array|string $message
	 * @param string $permission
	 * @return Server $this
	 */
	function sendAdminBroadcast($message, string $permission = "everything"): Server
	{
		if(!is_array($message))
		{
			$message = Phpcraft::textToChat($message);
		}
		echo Phpcraft::chatToText($message, Phpcraft::FORMAT_ANSI)."\n";
		return $this->permissionBroadcast($permission, [
			"color" => "gray",
			"text" => "[Server: ",
			"extra" => [
				$message,
				[
					"color" => "gray",
					"text" => "]"
				]
			]
		]);
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
	 * Closes all client connections and server sockets.
	 *
	 * @param array|string $reason The reason for closing the server; chat object.
	 */
	function close($reason = [])
	{
		foreach($this->streams as $stream)
		{
			fclose($stream);
		}
		$this->streams = [];
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
