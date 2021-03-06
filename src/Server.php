<?php
namespace Phpcraft;
use Asyncore\Condition;
use Exception;
use hellsh\UUID;
use Phpcraft\
{Command\ServerCommandSender, Enum\ChatPosition, Event\ServerTickEvent, Exception\IOException, Exception\NoConnectionException, Packet\ClientboundChatMessagePacket, Packet\KeepAliveRequestPacket, Packet\ServerboundPacketId, Permission\Group};
use http\Client;
use SplObjectStorage;
/**
 * A basic Minecraft server.
 * This deals with connections, configurations, handshakes, status requests, keep alive packets, and teleportation confirmations.
 * This does not include implementing an entire server; that is what packet_function and IntegratedServer are for.
 */
class Server extends BareServer implements ServerCommandSender
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
	 * True if you'd like every client's config to be persisted across connections from the same client.
	 * This is true by default on the IntegratedServer.
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
	 * @var callable $join_function
	 */
	public $join_function = null;
	/**
	 * The function called when the server receives a packet from a client in state 3 (playing) unless it's a keep alive response with the ClientConnection and ServerboundPacketId as arguments.
	 *
	 * @var callable $packet_function
	 */
	public $packet_function = null;
	/**
	 * The function called when a client's disconnected from the server with the ClientConnection as argument.
	 *
	 * @var callable $disconnect_function
	 */
	public $disconnect_function = null;
	/**
	 * The function called when to get the server's response to a list ping request with the ClientConnection as argument or null if called internally to get list ping information, e.g. in a plugin.
	 * See the documentation of ServerConnection::getStatus for an example of all the data a server may respond with (excluding "ping"), or if you return null, the list ping won't be completed.
	 * Additionally, if you set "no_ping", the client will show "(no connection)" where usually the ping in ms would be.
	 *
	 * @var callable $list_ping_function
	 */
	public $list_ping_function = null;
	/**
	 * If true, clients using incompatible versions will not be prevented from logging in.
	 *
	 * @var bool $allow_incompatible_versions
	 * @since 0.5 Inverted from $deny_incompatible_versions
	 */
	public $allow_incompatible_versions = false;
	/**
	 * @var Condition $open_condition
	 * @since 0.2.1
	 */
	public $open_condition;
	protected $tick_loop;

	/**
	 * @param array<resource> $streams An array of streams created by stream_socket_server to listen for clients on.
	 * @param resource|null $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
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
					if($client->state == Connection::STATE_PLAY)
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
		$this->groups = [
			"default" => new Group($this, [])
		];
		$this->open_condition = new Condition(function()
		{
			return $this->isOpen();
		});
		$this->open_condition->add(function()
		{
			$this->handle(true);
		}, 0.001);
		$this->tick_loop = $this->open_condition->add(function(bool $lagging)
		{
			PluginManager::fire(new ServerTickEvent($this, $lagging));
		}, 0.05);
	}

	/**
	 * Returns true if the server has at least one socket to listen for new connections on or at least one client.
	 *
	 * @return bool
	 */
	function isOpen(): bool
	{
		return count($this->streams) > 0 || count($this->clients) > 0;
	}

	private function handle(bool $full): void
	{
		if($full)
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
		}
		foreach($this->clients as $con)
		{
			assert($con instanceof ClientConnection);
			if($full && $con->isOpen())
			{
				try
				{
					if($con->state == Connection::STATE_HANDSHAKE)
					{
						switch($con->handleInitialPacket())
						{
							case 1:
								if($this->allow_incompatible_versions && !Versions::protocolSupported($con->protocol_version))
								{
									$con->disconnect("You're using an incompatible version.");
								}
								else
								{
									$con->disconnect_after = microtime(true) + 3;
								}
								break;
							case 2: // Legacy List Ping
								$json = ($this->list_ping_function)($con);
								if($json)
								{
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
									$data = "§1\x00127\x00".@$json["version"]["name"]."\x00".ChatComponent::fromArray($json["description"])
																										  ->toString(ChatComponent::FORMAT_SILCROW)."\x00".$json["players"]["online"]."\x00".$json["players"]["max"];
									$con->writeByte(0xFF);
									$con->writeShort(mb_strlen($data));
									$con->writeRaw(mb_convert_encoding($data, "utf-16be"));
									$con->send(true);
								}
								$con->close();
						}
					}
					else
					{
						while(($packet_id = $con->readPacket(0)) !== false)
						{
							if($con->state == Connection::STATE_PLAY) // Playing
							{
								$packetId = ServerboundPacketId::getById($packet_id, $con->protocol_version);
								if($packetId === null)
								{
									$con->disconnect("Invalid packet ID: ".dechex($packet_id));
									break;
								}
								if($packetId->name == "keep_alive_response")
								{
									$con->next_heartbeat = microtime(true) + 15;
									$con->disconnect_after = 0;
								}
								else if($packetId->name == "teleport_confirm")
								{
									if(gmp_cmp($con->readVarInt(), $con->tpidCounter->current()) == 0)
									{
										$con->tp_confirm_deadline = 0;
									}
								}
								else if($this->packet_function)
								{
									($this->packet_function)($con, $packetId);
								}
							}
							else if($con->state == Connection::STATE_LOGIN) // Login
							{
								if($packet_id == 0x00) // Login Start
								{
									$con->username = $con->readString();
									if(Account::validateUsername($con->username))
									{
										if($this->private_key)
										{
											$con->disconnect_after = microtime(true) + 10;
											$con->sendEncryptionRequest($this->private_key);
										}
										else
										{
											$con->disconnect_after = 0;
											$con->finishLogin(UUID::name("OfflinePlayer:".$con->username), $this->eidCounter, $this->compression_threshold);
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
									$con->handleEncryptionResponse($this->private_key, function($res) use (&$con)
									{
										if(!is_array($res))
										{
											return;
										}
										Phpcraft::$user_cache->set($res["id"], $con->username);
										$con->finishLogin(new UUID($res["id"]), $this->eidCounter, $this->compression_threshold);
										foreach($this->clients as $client)
										{
											if($client !== $con && $client->state == Connection::STATE_PLAY && $client->username == $con->username)
											{
												$client->disconnect(["text" => "You've logged in from a different location."]);
												$this->handle(false); // Properly dispose of $client before continuing with a new connection using the same identity to avoid issues.
											}
										}
										if($this->join_function)
										{
											try
											{
												($this->join_function)($con);
											}
											catch(Exception $e)
											{
												$con->disconnect(get_class($e).": ".$e->getMessage());
											}
										}
										$con->next_heartbeat = microtime(true) + 15;
									});
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
									if($json)
									{
										if($no_ping = isset($json["no_ping"]))
										{
											unset($json["no_ping"]);
										}
										$con->writeVarInt(0x00);
										$con->writeString(json_encode($json));
										$con->send();
									}
									else
									{
										$no_ping = true;
									}
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
						$con->disconnect("Keep alive timeout");
						continue;
					}
					if($con->tp_confirm_deadline != 0 && $con->tp_confirm_deadline <= microtime(true))
					{
						$con->disconnect("Teleportation confirmation timeout");
						continue;
					}
					if($con->next_heartbeat != 0 && $con->next_heartbeat <= microtime(true))
					{
						(new KeepAliveRequestPacket(time()))->send($con);
						$con->next_heartbeat = 0;
						$con->disconnect_after = microtime(true) + 30;
					}
				}
				catch(NoConnectionException $ignored)
				{
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
				$this->clients->detach($con);
				if($this->disconnect_function)
				{
					($this->disconnect_function)($con);
				}
			}
		}
	}

	/**
	 * @param array<string,array<string,string|array<string>>> $groups
	 * @return Server
	 */
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
	 * Returns the group with the given name or null if not found.
	 *
	 * @param string $name
	 * @return Group|null
	 */
	function getGroup(string $name): ?Group
	{
		return @$this->groups[$name];
	}

	/**
	 * Returns true if the server has at least one socket to listen for new connections.
	 *
	 * @return bool
	 */
	function isListening(): bool
	{
		return count($this->streams) > 0;
	}

	/**
	 * Returns the ports the server is listening on.
	 *
	 * @return array<int>
	 */
	function getPorts(): array
	{
		$ports = [];
		foreach($this->streams as $stream)
		{
			$name = stream_socket_get_name($stream, false);
			array_push($ports, intval(substr($name, strpos($name, ":", -6) + 1)));
		}
		return $ports;
	}

	/**
	 * Returns the "description" key from $this->list_ping_function's return array, cast to ChatComponent.
	 *
	 * @return ChatComponent
	 * @see Server::$list_ping_function
	 */
	function getMotd(): ChatComponent
	{
		return ChatComponent::cast(($this->list_ping_function)()["description"]);
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
	 * Sends a message to all players and prints it to the console.
	 *
	 * @param array|string|null|ChatComponent $msg
	 * @param int $position
	 * @return Server $this
	 */
	function broadcast($msg, int $position = ChatPosition::SYSTEM): Server
	{
		$msg = ChatComponent::cast($msg);
		echo $msg->toString(ChatComponent::FORMAT_ANSI)."\n";
		$msg = new ClientboundChatMessagePacket($msg);
		foreach($this->getPlayers() as $c)
		{
			try
			{
				$msg->send($c);
			}
			catch(Exception $ignored)
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
			if($client->state == Connection::STATE_PLAY)
			{
				array_push($clients, $client);
			}
		}
		return $clients;
	}

	/**
	 * Sends a message to the server console and all players with the given permission, e.g. "everything" for administrators.
	 *
	 * @param array|string|null|ChatComponent $msg
	 * @param string $permission
	 * @return Server
	 */
	function adminBroadcast($msg, string $permission = "everything"): Server
	{
		$msg = ChatComponent::cast($msg);
		echo $msg->toString(ChatComponent::FORMAT_ANSI)."\n";
		return $this->permissionBroadcast($permission, $msg);
	}

	/**
	 * Sends a message to all clients in playing state with the given permission.
	 *
	 * @param string $permission
	 * @param array|string|null|ChatComponent $msg
	 * @param int $position
	 * @return Server $this
	 */
	function permissionBroadcast(string $permission, $msg, int $position = ChatPosition::SYSTEM): Server
	{
		$msg = new ClientboundChatMessagePacket(ChatComponent::cast($msg));
		foreach($this->clients as $c)
		{
			assert($c instanceof ClientConnection);
			try
			{
				if($c->state == Connection::STATE_PLAY && $c->hasPermission($permission))
				{
					$msg->send($c);
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
	 * @param array|string|null|ChatComponent $message
	 * @param string $permission
	 * @return void
	 */
	function sendAdminBroadcast($message, string $permission = "everything"): void
	{
		$message = ChatComponent::cast($message);
		echo $message->toString(ChatComponent::FORMAT_ANSI)."\n";
		$this->permissionBroadcast($permission, ChatComponent::text("[Server: ")
															 ->gray()
															 ->add($message)
															 ->add("]"));
	}

	/**
	 * Gets the ClientConfiguration of a player who might be offline.
	 *
	 * @param string|UUID $name_or_uuid
	 * @return ClientConfiguration|null
	 */
	function getOfflinePlayer(string $name_or_uuid): ?ClientConfiguration
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
	function getPlayer($name_or_uuid): ?ClientConnection
	{
		foreach($this->clients as $client)
		{
			assert($client instanceof ClientConnection);
			if($client->state == Connection::STATE_PLAY && ($client->username == $name_or_uuid || $client->uuid == $name_or_uuid))
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
	 * @param array|string|null|ChatComponent $message
	 * @return void
	 */
	function sendMessage($message): void
	{
		echo ChatComponent::cast($message)
						  ->toString(ChatComponent::FORMAT_ANSI)."\n";
	}

	/**
	 * Closes all server listen sockets and client connections.
	 *
	 * @param array|string|null|ChatComponent $reason The reason for closing the server, sent to clients before disconnecting them.
	 * @return void
	 */
	function close($reason = null): void
	{
		$this->softClose();
		foreach($this->clients as $client)
		{
			assert($client instanceof ClientConnection);
			$client->disconnect($reason);
		}
	}

	/**
	 * Closes all server listen sockets but keeps connected clients.
	 *
	 * @return void
	 */
	function softClose(): void
	{
		foreach($this->streams as $stream)
		{
			stream_socket_shutdown($stream, STREAM_SHUT_RDWR);
			fclose($stream);
		}
		$this->streams = [];
	}

	function hasPermission(string $permission): bool
	{
		return true;
	}

	/**
	 * Available in accordance with the CommandSender interface.
	 *
	 * @return bool true
	 */
	function hasServer(): bool
	{
		return true;
	}

	/**
	 * Available in accordance with the CommandSender interface.
	 *
	 * @return Server $this
	 */
	function getServer(): ?Server
	{
		return $this;
	}

	function hasPosition(): bool
	{
		return false;
	}

	function getPosition(): ?Point3D
	{
		return null;
	}
}
