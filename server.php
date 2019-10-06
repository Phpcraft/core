<?php
/** @noinspection DuplicatedCode */
echo "Phpcraft PHP Minecraft Server\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php server.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{ClientConnection, Command\Command, Event\ServerChatEvent, Event\ServerChunkBorderEvent, Event\ServerClientSettingsEvent, Event\ServerConsoleEvent, Event\ServerFlyingChangeEvent, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerMovementEvent, Event\ServerOnGroundChangeEvent, Event\ServerPacketEvent, Event\ServerRotationEvent, Event\ServerTickEvent, Exception\IOException, Packet\ClientSettingsPacket, Packet\ServerboundPacket, Phpcraft, PlainUserInterface, PluginManager, Server, UserInterface, Versions};
$options = [
	"offline" => false,
	"port" => 25565,
	"nocolor" => false,
	"plain" => false
];
for($i = 1; $i < count($argv); $i++)
{
	$arg = $argv[$i];
	while(substr($arg, 0, 1) == "-")
	{
		$arg = substr($arg, 1);
	}
	if(($o = strpos($arg, "=")) !== false)
	{
		$n = substr($arg, 0, $o);
		$v = substr($arg, $o + 1);
	}
	else
	{
		$n = $arg;
		$v = "";
	}
	switch($n)
	{
		case "port":
			$options[$n] = $v;
			break;
		case "offline":
		case "nocolor":
		case "plain":
			$options[$n] = true;
			break;
		case "?":
		case "help":
			echo "port=<port>  bind to port <port>\n";
			echo "offline      disables online mode and allows cracked players\n";
			echo "nocolor      disallows players to use '&' to write colorfully\n";
			echo "plain        uses the plain user interface e.g. for writing logs to a file\n";
			exit;
		default:
			die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}
try
{
	$ui = ($options["plain"] ? new PlainUserInterface() : new UserInterface("PHP Minecraft Server"));
}
catch(RuntimeException $e)
{
	echo "Since you're on PHP <7.2.0 and Windows <10.0.10586, the plain user interface is forcefully enabled.\n";
	$ui = new PlainUserInterface();
}
if($options["offline"])
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
$ui->add("Binding to port ".$options["port"]."... ")
   ->render();
$stream = stream_socket_server("tcp://0.0.0.0:".$options["port"], $errno, $errstr) or die(" {$errstr}\n");
$server = new Server($stream, $private_key);
$ui->append("Success!")
   ->render();
if(!is_dir("config"))
{
	mkdir("config");
}
if(is_file("config/server.json"))
{
	$config = json_decode(file_get_contents("config/server.json"), true);
}
else
{
	$config = [];
}
if(!array_key_exists("groups", $config))
{
	$config["groups"] = [
		"default" => [
			"allow" => [
				"use /gamemode",
				"use /metadata",
				"change the world"
			]
		],
		"user" => [
			"inherit" => "default",
			"allow" => [
				"use /abilities",
				"use chromium"
			]
		],
		"admin" => [
			"allow" => "everything"
		]
	];
}
if(!array_key_exists("motd", $config))
{
	$config["motd"] = [
		"text" => "A ",
		"extra" => [
			[
				"text" => "Phpcraft",
				"color" => "red",
				"italic" => true
			],
			[
				"text" => " Server\n§aNow with 100% more §kmagic§r§a!"
			]
		]
	];
}
if(!array_key_exists("show_no_connection_instead_of_ping_in_server_list", $config))
{
	$config["show_no_connection_instead_of_ping_in_server_list"] = false;
}
if(!array_key_exists("compression_threshold", $config))
{
	$config["compression_threshold"] = 256;
}
file_put_contents("config/server.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
$server->compression_threshold = $config["compression_threshold"];
$server->setGroups($config["groups"]);
if($ui instanceof UserInterface)
{
	$ui->setInputPrefix("[Server] ");
}
echo "Loading plugins...\n";
PluginManager::loadPlugins();
echo "Loaded ".PluginManager::$loaded_plugins->count()." plugin(s).\n";
$ui->render();
$ui->tabcomplete_function = function(string $word)
{
	global $server;
	$word = strtolower($word);
	$completions = [];
	$len = strlen($word);
	foreach($server->clients as $c)
	{
		if($c->state == 3 && strtolower(substr($c->username, 0, $len)) == $word)
		{
			array_push($completions, $c->username);
		}
	}
	return $completions;
};
$default_list_ping_function = $server->list_ping_function;
$server->list_ping_function = function(ClientConnection $con = null) use (&$config, &$default_list_ping_function)
{
	$data = $default_list_ping_function($con);
	$data["description"] = $config["motd"];
	$data["modinfo"] = [
		"type" => "FML",
		"modList" => []
	];
	if($config["show_no_connection_instead_of_ping_in_server_list"])
	{
		$data["no_ping"] = true;
	}
	return $data;
};
$server->join_function = function(ClientConnection $con)
{
	if(!Versions::protocolSupported($con->protocol_version))
	{
		$con->disconnect(["text" => "You're using an incompatible version."]);
		return;
	}
	global $ui, $server;
	foreach($server->clients as $client)
	{
		if($client !== $con && $client->state == 3 && $client->username == $con->username)
		{
			if($server->isOnlineMode())
			{
				$client->disconnect(["text" => "You've logged in from a different location."]);
				$server->handle();
			}
			else
			{
				$solved = false;
				if(strlen($con->username) <= 13)
				{
					for($i = 2; $i <= 9; $i++)
					{
						if($server->getPlayer("{$con->username}($i)") === null)
						{
							$con->username .= "($i)";
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
					$con->state = 2; // prevent ServerLeaveEvent being fired
					return;
				}
			}
		}
	}
	if(PluginManager::fire(new ServerJoinEvent($server, $con)))
	{
		$con->close();
		return;
	}
	$msg = [
		"color" => "yellow",
		"translate" => "multiplayer.player.joined",
		"with" => [
			[
				"text" => $con->username
			]
		]
	];
	$ui->add(Phpcraft::chatToText($msg, 1));
	$msg = json_encode($msg);
	foreach($server->getPlayers() as $c)
	{
		try
		{
			$c->startPacket("clientbound_chat_message");
			$c->writeString($msg);
			$c->writeByte(1);
			$c->send();
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
};
$server->packet_function = function(ClientConnection $con, ServerboundPacket $packetId)
{
	global $options, $ui, $server;
	if(PluginManager::fire(new ServerPacketEvent($server, $con, $packetId)))
	{
		return;
	}
	if($packetId->name == "position" || $packetId->name == "position_and_look" || $packetId->name == "look" || $packetId->name == "no_movement")
	{
		if($packetId->name == "position" || $packetId->name == "position_and_look")
		{
			$prev_pos = $con->pos;
			$con->pos = $con->readPrecisePosition();
			if(PluginManager::fire(new ServerMovementEvent($server, $con, $prev_pos)))
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
					if(PluginManager::fire(new ServerChunkBorderEvent($server, $con, $prev_pos, $prev_chunk_x, $prev_chunk_z)))
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
			if(PluginManager::fire(new ServerRotationEvent($server, $con, $prev_yaw, $prev_pitch)))
			{
				$con->rotate($prev_yaw, $prev_pitch);
			}
		}
		$_on_ground = $con->on_ground;
		$con->on_ground = $con->readBoolean();
		if($_on_ground != $con->on_ground)
		{
			PluginManager::fire(new ServerOnGroundChangeEvent($server, $con, $_on_ground));
		}
	}
	else if($packetId->name == "entity_action")
	{
		if($con->readVarInt() != $con->eid)
		{
			throw new IOException("Entity ID mismatch in Entity Action packet");
		}
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
	}
	else if($packetId->name == "serverbound_abilities")
	{
		$flags = $con->readByte();
		$_flying = $con->flying;
		$con->flying = ($flags & 0x02);
		if($_flying != $con->flying)
		{
			PluginManager::fire(new ServerFlyingChangeEvent($server, $con, $_flying));
		}
	}
	else if($packetId->name == "serverbound_chat_message")
	{
		$msg = $con->readString($con->protocol_version < 314 ? 100 : 256);
		if(Command::handleMessage($con, $msg) || PluginManager::fire(new ServerChatEvent($server, $con, $msg)))
		{
			return;
		}
		if($options["nocolor"])
		{
			$msg = ["text" => $msg];
		}
		else
		{
			$msg = Phpcraft::textToChat($msg, true);
		}
		$msg = [
			"translate" => "chat.type.text",
			"with" => [
				[
					"text" => $con->username
				],
				$msg
			]
		];
		$ui->add(Phpcraft::chatToText($msg, 1));
		$msg = json_encode($msg);
		foreach($server->getPlayers() as $c)
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
		PluginManager::fire(new ServerClientSettingsEvent($server, $con, $packet));
		$con->render_distance = max(min($packet->render_distance, 32), 2);
	}
};
$server->disconnect_function = function(ClientConnection $con)
{
	global $ui, $server;
	if($con->state == 3 && !PluginManager::fire(new ServerLeaveEvent($server, $con)) && $con->hasPosition())
	{
		$msg = [
			"color" => "yellow",
			"translate" => "multiplayer.player.left",
			"with" => [
				[
					"text" => $con->username
				]
			]
		];
		$ui->add(Phpcraft::chatToText($msg, 1));
		$msg = json_encode($msg);
		foreach($server->getPlayers() as $c)
		{
			if($c !== $con)
			{
				try
				{
					$c->startPacket("clientbound_chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
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
	}
};
$server->persist_configs = true;
$next_tick = microtime(true) + 0.05;
do
{
	$start = microtime(true);
	$server->accept();
	$server->handle();
	while($msg = $ui->render(true))
	{
		if(Command::handleMessage($server, $msg) || PluginManager::fire(new ServerConsoleEvent($server, $msg)))
		{
			continue;
		}
		$msg = [
			"translate" => "chat.type.announcement",
			"with" => [
				[
					"text" => "Server"
				],
				[
					"text" => $msg
				]
			]
		];
		$ui->add(Phpcraft::chatToText($msg, 1));
		$server->broadcast($msg);
	}
	if($next_tick < microtime(true))
	{
		$next_tick += 0.05;
		PluginManager::fire(new ServerTickEvent($server, $next_tick < microtime(true)));
	}
	if(($remaining = (0.001 - (microtime(true) - $start))) > 0)
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while($server->isOpen());
