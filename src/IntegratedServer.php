<?php
namespace Phpcraft;
use Exception;
use hellsh\UUID;
use Phpcraft\
{Command\Command, Enum\Gamemode, Event\ServerChatEvent, Event\ServerChunkBorderEvent, Event\ServerClientMetadataEvent, Event\ServerClientSettingsEvent, Event\ServerFlyingChangeEvent, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerMovementEvent, Event\ServerOnGroundChangeEvent, Event\ServerPacketEvent, Event\ServerRotationEvent, Exception\IOException, Packet\ClientSettingsPacket, Packet\JoinGamePacket, Packet\PluginMessage\ClientboundBrandPluginMessagePacket, Packet\ServerboundPacketId};
use RuntimeException;
class IntegratedServer extends Server
{
	/**
	 * @var string $name
	 */
	public $name;
	/**
	 * @var PlainUserInterface $ui
	 */
	public $ui;
	/**
	 * @var array<string,mixed> $config
	 */
	public $config;
	/**
	 * @var array<string,mixed> $custom_config_defaults
	 */
	public $custom_config_defaults;
	/**
	 * The function called after the config has been reloaded.
	 *
	 * @var callable|null $config_reloaded_function
	 */
	public $config_reloaded_function;
	/**
	 * @var bool $provide_player_list
	 */
	public $provide_player_list = true;
	/**
	 * Offline mode only: If true, duplicate player names will be resolved by adding (2), etc. to the end of the name, if possible. Otherwise, they will just be disconnected.
	 *
	 * @var bool $fix_duplicate_names
	 */
	public $fix_duplicate_names = true;
	/**
	 * @var bool $fire_join_event
	 */
	public $fire_join_event = true;

	/**
	 * @param string $name
	 * @param array $custom_config_defaults
	 * @param UserInterface|null $ui
	 * @param resource|null $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 */
	function __construct(string $name = "Phpcraft Integrated Server", array $custom_config_defaults = [], ?UserInterface $ui = null, $private_key = null)
	{
		parent::__construct([], $private_key);
		$this->name = $name;
		$this->custom_config_defaults = $custom_config_defaults;
		$this->ui = $ui ?? new PlainUserInterface();
		$this->persist_configs = true;
		$this->reloadConfig();
		if($this->ui instanceof FancyUserInterface)
		{
			$this->ui->setInputPrefix("[".$this->name."] ");
			$this->ui->tabcomplete_function = function(string $word)
			{
				$word = strtolower($word);
				$completions = [];
				$len = strlen($word);
				foreach($this->clients as $c)
				{
					if($c->state == Connection::STATE_PLAY && strtolower(substr($c->username, 0, $len)) == $word)
					{
						array_push($completions, $c->username);
					}
				}
				return $completions;
			};
		}
		$default_list_ping_function = $this->list_ping_function;
		$this->list_ping_function = function(ClientConnection $con = null) use (&$default_list_ping_function)
		{
			$data = $default_list_ping_function($con);
			if($this->config["server_list_appearance"]["show_question_marks_instead_of_player_count"])
			{
				unset($data["players"]);
			}
			$data["description"] = $this->config["server_list_appearance"]["description"];
			$data["modinfo"] = [
				"type" => "FML",
				"modList" => []
			];
			if($this->config["server_list_appearance"]["show_no_connection_instead_of_ping"])
			{
				$data["no_ping"] = true;
			}
			return $data;
		};
		$this->join_function = function(ClientConnection $con)
		{
			if(!Versions::protocolSupported($con->protocol_version))
			{
				$con->disconnect(["text" => "You're using an incompatible version."]);
				return;
			}
			foreach($this->clients as $client)
			{
				if($client !== $con && $client->state == Connection::STATE_PLAY && $client->username == $con->username)
				{
					if($this->isOnlineMode())
					{
						$client->disconnect(["text" => "You've logged in from a different location."]);
						$this->handle(false); // Properly dispose of $client before continuing with a new connection using the same identity to avoid issues.
					}
					else if($this->fix_duplicate_names)
					{
						$solved = false;
						if(strlen($con->username) <= 13)
						{
							for($i = 2; $i <= 9; $i++)
							{
								if($this->getPlayer("{$con->username}($i)") === null)
								{
									$con->username .= "($i)";
									$con->uuid = UUID::v3("OfflinePlayer:".$con->username);
									$con->sendMessage([
										"text" => "To avoid conflicts, your name has been changed to {$con->username}.",
										"color" => "red"
									]);
									$solved = true;
									break;
								}
							}
						}
						if(!$solved)
						{
							$con->disconnect([
								"text" => "",
								"extra" => [
									[
										"text" => "You",
										"italic" => true
									],
									[
										"text" => "'re already on this server, and the best solution I have is kicking "
									],
									[
										"text" => "you.",
										"bold" => true
									]
								]
							]);
							$con->state = Connection::STATE_LOGIN; // prevent ServerLeaveEvent being fired
							return;
						}
					}
					else
					{
						$con->disconnect(["text" => "I already have a ".$con->username."."]);
						$con->state = Connection::STATE_LOGIN; // prevent ServerLeaveEvent being fired
						return;
					}
				}
			}
			$packet = new JoinGamePacket();
			$packet->eid = $con->eid;
			$packet->gamemode = $con->gamemode = Gamemode::CREATIVE;
			$packet->render_distance = 32;
			$packet->send($con);
			(new ClientboundBrandPluginMessagePacket($this->name))->send($con);
			$con->setAbilitiesFromGamemode($con->gamemode)
				->sendAbilities();
			$con->startPacket("spawn_position");
			$con->writePosition($con->pos = new Point3D(0, 16, 0));
			$con->send();
			$con->teleport($con->pos);
			if($this->fire_join_event && PluginManager::fire(new ServerJoinEvent($this, $con)))
			{
				$con->close();
				return;
			}
			if($this->provide_player_list)
			{
				foreach($this->getPlayers() as $c)
				{
					try
					{
						$c->startPacket("player_info");
						$c->writeVarInt(0);
						$c->writeVarInt(1);
						$c->writeUUID($con->uuid);
						$c->writeString($con->username);
						$c->writeVarInt(0);
						$c->writeVarInt(-1);
						$c->writeVarInt(-1);
						$c->writeBoolean(false);
						$c->send();
						if($c !== $con)
						{
							$con->startPacket("player_info");
							$con->writeVarInt(0);
							$con->writeVarInt(1);
							$con->writeUUID($c->uuid);
							$con->writeString($c->username);
							$con->writeVarInt(0);
							$con->writeVarInt(0);
							$con->writeVarInt(-1);
							$con->writeBoolean(false);
							$con->send();
						}
					}
					catch(Exception $ignored)
					{
					}
				}
			}
		};
		$this->packet_function = function(ClientConnection $con, ServerboundPacketId $packetId)
		{
			if(PluginManager::fire(new ServerPacketEvent($this, $con, $packetId)))
			{
				return;
			}
			if($packetId->name == "position" || $packetId->name == "position_and_look" || $packetId->name == "look" || $packetId->name == "no_movement")
			{
				if($con->tp_confirm_deadline != 0)
				{
					return;
				}
				if($packetId->name == "position" || $packetId->name == "position_and_look")
				{
					$prev_pos = $con->pos;
					$con->pos = $con->readPrecisePosition();
					if(PluginManager::fire(new ServerMovementEvent($this, $con, $prev_pos)))
					{
						$con->teleport($prev_pos);
					}
					else
					{
						$chunk_x = ceil($con->pos->x / 16);
						$chunk_z = ceil($con->pos->z / 16);
						if($chunk_x != $con->chunk_x || $chunk_z != $con->chunk_z)
						{
							$prev_chunk_x = $con->chunk_x;
							$prev_chunk_z = $con->chunk_z;
							$con->chunk_x = $chunk_x;
							$con->chunk_z = $chunk_z;
							if(PluginManager::fire(new ServerChunkBorderEvent($this, $con, $prev_pos, $prev_chunk_x, $prev_chunk_z)))
							{
								$con->teleport($prev_pos);
							}
							else if($con->protocol_version >= 472)
							{
								$con->startPacket("update_view_position");
								$con->writeVarInt($con->chunk_x);
								$con->writeVarInt($con->chunk_z);
								$con->send();
							}
						}
					}
				}
				if($packetId->name == "position_and_look" || $packetId->name == "look")
				{
					$prev_yaw = $con->yaw;
					$prev_pitch = $con->pitch;
					$con->yaw = $con->readFloat();
					if($con->yaw < 0 || $con->yaw > 360)
					{
						$con->yaw -= floor($con->yaw / 360) * 360;
					}
					$con->pitch = $con->readFloat();
					if($con->pitch < -90 || $con->pitch > 90)
					{
						throw new IOException("Invalid Y rotation: ".$con->pitch);
					}
					if(PluginManager::fire(new ServerRotationEvent($this, $con, $prev_yaw, $prev_pitch)))
					{
						$con->rotate($prev_yaw, $prev_pitch);
					}
				}
				$_on_ground = $con->on_ground;
				$con->on_ground = $con->readBoolean();
				if($_on_ground != $con->on_ground)
				{
					PluginManager::fire(new ServerOnGroundChangeEvent($this, $con, $_on_ground));
				}
			}
			else if($packetId->name == "entity_action")
			{
				if(gmp_cmp($con->readVarInt(), $con->eid) != 0)
				{
					throw new IOException("Entity ID mismatch in Entity Action packet");
				}
				$prev_metadata = clone $con->entityMetadata;
				switch($con->readByte())
				{
					case 0:
						$con->entityMetadata->crouching = true;
						break;
					case 1:
						$con->entityMetadata->crouching = false;
						break;
					case 3:
						$con->entityMetadata->sprinting = true;
						break;
					case 4:
						$con->entityMetadata->sprinting = false;
						break;
				}
				if($con->entityMetadata->crouching !== $prev_metadata->crouching || $con->entityMetadata->sprinting !== $prev_metadata->sprinting)
				{
					if(PluginManager::fire(new ServerClientMetadataEvent($this, $con, $prev_metadata)))
					{
						// TODO: Revert metadata when cancelled.
					}
				}
			}
			else if($packetId->name == "serverbound_abilities")
			{
				$flags = $con->readByte();
				$_flying = $con->flying;
				$con->flying = ($flags & 0x02);
				if($_flying != $con->flying)
				{
					PluginManager::fire(new ServerFlyingChangeEvent($this, $con, $_flying));
				}
			}
			else if($packetId->name == "serverbound_chat_message")
			{
				$msg = $con->readString($con->protocol_version < 314 ? 100 : 256);
				if(Command::handleMessage($con, $msg) || PluginManager::fire(new ServerChatEvent($this, $con, $msg)))
				{
					return;
				}
				$msg = [
					"translate" => "chat.type.text",
					"with" => [
						[
							"text" => $con->username
						],
						[
							"text" => $msg
						]
					]
				];
				$this->ui->add(Phpcraft::chatToText($msg, Phpcraft::FORMAT_ANSI));
				$msg = json_encode($msg);
				foreach($this->getPlayers() as $c)
				{
					try
					{
						$c->startPacket("clientbound_chat_message");
						$c->writeString($msg);
						$c->writeByte(1);
						$c->send();
					}
					catch(Exception $ignored)
					{
					}
				}
			}
			else if($packetId->name == "client_settings")
			{
				$packet = ClientSettingsPacket::read($con);
				PluginManager::fire(new ServerClientSettingsEvent($this, $con, $packet));
				$con->render_distance = max(min($packet->render_distance, 32), 2);
			}
		};
		$this->disconnect_function = function(ClientConnection $con)
		{
			if($con->state == Connection::STATE_PLAY)
			{
				PluginManager::fire(new ServerLeaveEvent($this, $con));
				foreach($this->getPlayers() as $c)
				{
					try
					{
						$c->startPacket("player_info");
						$c->writeVarInt(4);
						$c->writeVarInt(1);
						$c->writeUUID($con->uuid);
						$c->send();
					}
					catch(Exception $ignored)
					{
					}
				}
			}
		};
	}

	/**
	 * @return void
	 */
	function reloadConfig(): void
	{
		if(!is_dir("config"))
		{
			mkdir("config");
		}
		if(is_file("config/".$this->name.".json"))
		{
			$this->config = json_decode(file_get_contents("config/".$this->name.".json"), true);
		}
		else
		{
			$this->config = [];
		}
		foreach($this->custom_config_defaults as $key => $default)
		{
			if(!array_key_exists($key, $this->config))
			{
				$this->config[$key] = $default;
			}
		}
		if(!array_key_exists("groups", $this->config))
		{
			$this->config["groups"] = [
				"default" => [
					"allow" => [
						"use /me",
						"use /gamemode",
						"use /metadata",
						"change the world"
					]
				],
				"user" => [
					"inherit" => "default",
					"allow" => [
						"use /abilities"
					]
				],
				"admin" => [
					"allow" => "everything"
				]
			];
		}
		if(!array_key_exists("ports", $this->config))
		{
			$this->config["ports"] = [25565];
		}
		if(!array_key_exists("server_list_appearance", $this->config))
		{
			$this->config["server_list_appearance"] = [];
		}
		if(!array_key_exists("description", $this->config["server_list_appearance"]))
		{
			$this->config["server_list_appearance"]["description"] = [
				"text" => "A {$this->name} instance"
			];
		}
		if(!array_key_exists("show_question_marks_instead_of_player_count", $this->config["server_list_appearance"]))
		{
			$this->config["server_list_appearance"]["show_question_marks_instead_of_player_count"] = false;
		}
		if(!array_key_exists("show_no_connection_instead_of_ping", $this->config["server_list_appearance"]))
		{
			$this->config["server_list_appearance"]["show_no_connection_instead_of_ping"] = false;
		}
		if(!array_key_exists("compression_threshold", $this->config))
		{
			$this->config["compression_threshold"] = 256;
		}
		$this->saveConfig();
		$this->compression_threshold = $this->config["compression_threshold"];
		$this->setGroups($this->config["groups"]);
		$open_ports = [];
		$streams_ = [];
		foreach($this->streams as $stream)
		{
			$name = stream_socket_get_name($stream, false);
			$port = intval(substr($name, strpos($name, ":", -6) + 1));
			if(in_array($port, $this->config["ports"]))
			{
				array_push($open_ports, $port);
				array_push($streams_, $stream);
			}
			else
			{
				stream_socket_shutdown($stream, STREAM_SHUT_RDWR);
				fclose($stream);
				$this->adminBroadcast("Unbound from ".$name);
			}
		}
		$this->streams = $streams_;
		foreach($this->config["ports"] as $port)
		{
			if(in_array($port, $open_ports))
			{
				continue;
			}
			foreach([
				"0.0.0.0:",
				"[::0]:"
			] as $prefix)
			{
				if($stream = @stream_socket_server("tcp://".$prefix.$port, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN))
				{
					stream_set_blocking($stream, false);
					array_push($this->streams, $stream);
					$this->adminBroadcast("Successfully bound to ".$prefix.$port);
				}
				else
				{
					$this->adminBroadcast("Failed to bind to ".$prefix.$port);
				}
			}
		}
		if(!$this->isListening() && $this->isOpen())
		{
			$this->adminBroadcast($this->name." is not listening on any ports. It will shutdown once empty.");
		}
		else if($this->config_reloaded_function)
		{
			($this->config_reloaded_function)();
		}
	}

	/**
	 * @return void
	 */
	function saveConfig(): void
	{
		file_put_contents("config/".$this->name.".json", json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}

	/**
	 * Starts the integrated server using $argv.
	 *
	 * @param string $name
	 * @param array $custom_config_defaults
	 * @return IntegratedServer
	 */
	static function cliStart(string $name = "Phpcraft Integrated Server", array $custom_config_defaults = []): IntegratedServer
	{
		global $argv;
		$options = [
			"offline" => false,
			"plain" => false
		];
		for($i = 1; $i < count($argv); $i++)
		{
			$arg = ltrim($argv[$i], "-/");
			switch($arg)
			{
				case "offline":
				case "plain":
					$options[$arg] = true;
					break;
				case "?":
				case "help":
					echo "offline      disables online mode and allows cracked players\n";
					echo "plain        uses the plain user interface e.g. for writing logs to a file\n";
					exit;
				default:
					die("Unknown flag '$arg' -- use 'help' to get a list of supported flags.\n");
			}
		}
		return self::start($name, $options["offline"], $options["plain"], $custom_config_defaults);
	}

	/**
	 * Starts the integrated server using the given settings.
	 *
	 * @param string $name
	 * @param bool $offline
	 * @param bool $plain
	 * @param array $custom_config_defaults
	 * @return IntegratedServer
	 */
	static function start(string $name = "Phpcraft Integrated Server", bool $offline = false, bool $plain = false, array $custom_config_defaults = []): IntegratedServer
	{
		try
		{
			$ui = ($plain ? new PlainUserInterface() : new FancyUserInterface($name));
		}
		catch(RuntimeException $e)
		{
			echo "Since you're on PHP <7.2.0 and Windows <10.0.10586, the plain user interface is forcefully enabled.\n";
			$ui = new PlainUserInterface();
		}
		if($offline)
		{
			$private_key = null;
		}
		else
		{
			$ui->add("Generating 1024-bit RSA keypair... ")
			   ->render();
			$args = [
				"private_key_bits" => 1024,
				"private_key_type" => OPENSSL_KEYTYPE_RSA
			];
			if(Phpcraft::isWindows())
			{
				$args["config"] = __DIR__."/openssl.cnf";
			}
			$private_key = openssl_pkey_new($args) or die("Failed to generate private key.\n");
			$ui->append("Done.")
			   ->render();
		}
		return new static($name, $custom_config_defaults, $ui, $private_key);
	}
}
