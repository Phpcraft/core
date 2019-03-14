<?php
echo "Phpcraft PHP Minecraft Client\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php client.php`.\n");
}
require "vendor/autoload.php";

$options = [];
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
		case "plain":
		case "noreconnect":
		$options[$n] = true;
		break;

		case "online":
		if($v == "on")
		{
			$options[$n] = true;
		}
		else if($v == "off")
		{
			$options[$n] = false;
		}
		else die("Value for argument '{$n}' has to be either 'on' or 'off'.\n");
		break;

		case "name":
		case "server":
		case "lang":
		case "joinmsg":
		case "version":
		$options[$n] = $v;
		break;

		case "?":
		case "help":
		echo "online=<on/off>   set online or offline mode\n";
		echo "name=<name>       skip name input and use <name> as name\n";
		echo "server=<server>   skip server input and connect to <server>\n";
		echo "lang=<lang>       use Minecraft language <lang>, default: en_GB\n";
		echo "joinmsg=<msg>     as soon as connected, <msg> will be handled\n";
		echo "version=<version> don't check server version, connect using <version>\n";
		echo "plain             replaces the fancy user interface with a plain one\n";
		echo "noreconnect       don't reconnect when server disconnects\n";
		exit;

		default:
		die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}

$am = \Phpcraft\AssetsManager::fromMinecraftVersion(\Phpcraft\Phpcraft::getSupportedMinecraftVersions()[0]);
if(empty($options["lang"]))
{
	$options["lang"] = "en_GB";
}
else
{
	$arr = explode("_", $options["lang"]);
	if(count($arr) != 2)
	{
		echo "'".$options["lang"]."' is not a valid language code, using en_GB.\n";
		$options["lang"] = "en_GB";
	}
	else
	{
		$options["lang"] = strtolower($arr[0])."_".strtoupper($arr[1]);		
		if(!$am->doesAssetExist("minecraft/lang/".strtolower($options["lang"]).".json"))
		{
			echo "Couldn't find translations for ".$options["lang"].", using en_GB.\n";
			$options["lang"] = "en_GB";
		}
	}
}
$translations = json_decode(file_get_contents($am->downloadAsset("minecraft/lang/".strtolower($options["lang"]).".json")), true);

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

$online = false;
if(isset($options["online"]) && $options["online"] === true)
{
	$online = true;
}
else if(!isset($options["online"]))
{
	echo "Would you like to join premium servers? (y/N) ";
	if(substr(trim(fgets($stdin)), 0, 1) == "y")
	{
		$online = true;
	}
}

$name = "";
if(isset($options["name"]))
{
	$name = $options["name"];
}
while($name == "")
{
	if($online)
	{
		echo "What's your Mojang account email address? (username if unmigrated) ";
		$name = trim(fgets($stdin));
	}
	else
	{
		echo "How would you like to be called in-game? [PhpcraftUser] ";
		$name = trim(fgets($stdin));
		if($name == "")
		{
			$name = "PhpcraftUser";
			break;
		}
		if(!\Phpcraft\Phpcraft::validateName($name))
		{
			echo "Invalid name.\n";
			$name = "";
		}
	}
}
$account = new \Phpcraft\Account($name);
if($online && !$account->loginUsingProfiles())
{
	do
	{
		readline_callback_handler_install("What's your account password? (hidden) ", function($input){});
		if(!($pass = trim(fgets($stdin))))
		{
			echo "No password provided.\n";
		}
		else if($error = $account->login($pass))
		{
			echo $error."\n";
		}
		else
		{
			echo "\n";
			break;
		}
	}
	while(true);
	readline_callback_handler_remove();
}

$server = "";
if(isset($options["server"]))
{
	$server = $options["server"];
}
if(!$server)
{
	echo "What server would you like to join? [localhost] ";
	$server = trim(fgets($stdin));
	if(!$server)
	{
		$server = "localhost";
	}
}
fclose($stdin);
$ui = (isset($options["plain"]) ? new \Phpcraft\UserInterface() : new \Phpcraft\FancyUserInterface("PHP Minecraft Client", "github.com/timmyrs/Phpcraft"));
$ui->add("Resolving... ")->render();
$server = \Phpcraft\Phpcraft::resolve($server);
$serverarr = explode(":", $server);
if(count($serverarr) != 2)
{
	$ui->append("Failed to resolve name. Got {$server}")->render();
	exit;
}
$ui->append("Resolved to {$server}")->render();
if(empty($options["version"]))
{
	$info = \Phpcraft\Phpcraft::getServerStatus($serverarr[0], $serverarr[1], 3, 1);
	if(empty($info) || empty($info["version"]) || empty($info["version"]["protocol"]))
	{
		$ui->add("Invalid status: ".json_encode($info))->render();
		exit;
	}
	$protocol_version = $info["version"]["protocol"];
	if(!($minecraft_versions = \Phpcraft\Phpcraft::getMinecraftVersionsFromProtocolVersion($protocol_version)))
	{
		$ui->add("This server uses an unknown protocol version: {$protocol_version}")->render();
		exit;
	}
	$minecraft_version = $minecraft_versions[0];
}
else
{
	$minecraft_version = $options["version"];
	$protocol_version = \Phpcraft\Phpcraft::getProtocolVersionFromMinecraftVersion($minecraft_version);
	if($protocol_version === NULL)
	{
		$ui->add("Unknown Minecraft version: {$minecraft_version}")->render();
		exit;
	}
}
\Phpcraft\PluginManager::$platform = "phpcraft:client";
function autoloadPlugins()
{
	global $ui, $minecraft_version, $protocol_version;
	echo "Autoloading plugins...\n";
	\Phpcraft\PluginManager::$loaded_plugins = [];
	\Phpcraft\PluginManager::autoloadPlugins();
	\Phpcraft\PluginManager::fire(new \Phpcraft\Event("load", [
		"server_minecraft_version" => $minecraft_version,
		"server_protocol_version" => $protocol_version
	]));
	echo "Loaded ".count(\Phpcraft\PluginManager::$loaded_plugins)." plugin(s).\n";
	$ui->render();
}
autoloadPlugins();
function handleConsoleMessage($msg)
{
	if($msg == "")
	{
		return;
	}
	global $con;
	if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("console_message", [
		"message" => $msg,
		"connection" => $con
	])))
	{
		return;
	}
	global $ui;
	$ui->add($msg);
	$send = true;
	if(substr($msg, 0, 2) == "..")
	{
		$msg = substr($msg, 1);
	}
	else if(substr($msg, 0, 1) == ".")
	{
		$send = false;
		$msg = substr($msg, 1);
		$args = explode(" ", $msg);
		switch($args[0])
		{
			case "?":
			case "help":
			$ui->add("Yay! You've found commands, which start with a period.");
			$ui->add("If you want to send a message starting with a period, use two periods.");
			$ui->add("?, help                    shows this help");
			$ui->add("pos                        returns the current position");
			$ui->add("move <y>, move <x> [y] <z> initates movement");
			$ui->add("rot <yaw> <pitch>          change yaw and pitch degrees");
			$ui->add("list                       lists all players in the player list");
			$ui->add("entities                   lists all player entities");
			$ui->add("follow <name>              follows <name>'s player entity");
			$ui->add("unfollow                   stops following whoever is being followed");
			$ui->add("slot <1-9>                 sets selected hotbar slot");
			$ui->add("hit                        swings the main hand");
			$ui->add("use                        uses the held item");
			$ui->add("reload                     reloads all autoloaded plugins");
			$ui->add("reconnect                  reconnects to the server");
			$ui->add("quit, disconnect           disconnects from the server");
			break;

			case "pos":
			global $x, $y, $z;
			$ui->add("$x $y $z");
			break;

			case "move":
			global $followEntity;
			if($followEntity !== false)
			{
				$ui->add("I'm currently following someone.");
			}
			else
			{
				global $motion_x, $motion_y, $motion_z;
				if(isset($args[1]) && isset($args[2]) && isset($args[3]))
				{
					$motion_x += doubleval($args[1]);
					$motion_y += doubleval($args[2]);
					$motion_z += doubleval($args[3]);
					$ui->add("Understood.");
				}
				else if(isset($args[1]) && isset($args[2]))
				{
					$motion_x += doubleval($args[1]);
					$motion_z += doubleval($args[2]);
					$ui->add("Understood.");
				}
				else if(isset($args[1]))
				{
					$motion_y += doubleval($args[1]);
					$ui->add("Understood.");
				}
				else
				{
					$ui->add("Syntax: .move <y>, .move <x> [y] <z>");
				}
			}
			break;

			case "rot":
			global $followEntity;
			if($followEntity !== false)
			{
				$ui->add("I'm currently following someone.");
			}
			else if(isset($args[1]) && isset($args[2]))
			{
				global $yaw, $pitch;
				$yaw = floatval($args[1]);
				$pitch = floatval($args[2]);
				$ui->add("Understood.");
			}
			else
			{
				$ui->add("Syntax: .rot <yaw> <pitch>");
			}
			break;

			case "hit":
			$con->startPacket("animation");
			if($con->protocol_version > 47)
			{
				$con->writeVarInt(0);
			}
			$con->send();
			$ui->add("Done.");
			break;

			case "use":
			global $x, $y, $z;
			if($con->protocol_version > 47)
			{
				$con->startPacket("use_item");
				$con->writeVarInt(0);
			}
			else
			{
				$con->startPacket("player_block_placement");
				$con->writePosition($x, $y, $z);
				$con->writeByte(-1); // Face
				$con->writeShort(-1); // Slot
				$con->writeByte(-1); // Cursor X
				$con->writeByte(-1); // Cursor Y
				$con->writeByte(-1); // Cursor Z
			}
			$con->send();
			$ui->add("Done.");
			break;

			case "list":
			$gamemodes = [
				0 => "Survival",
				1 => "Creative",
				2 => "Adventure",
				3 => "Spectator"
			];
			global $players;
			foreach($players as $uuid => $player)
			{
				$ui->add($uuid."  ".$player["name"].str_repeat(" ", 17 - strlen($player["name"])).str_repeat(" ", 5 - strlen($player["ping"])).$player["ping"]." ms  ".$gamemodes[$player["gamemode"]]." Mode");
			}
			break;

			case "entities":
			global $entities;
			foreach($entities as $eid => $entity)
			{
				$ui->add($eid." ".$entity["x"]." ".$entity["y"]." ".$entity["z"]);
			}
			break;

			case "follow":
			if(isset($args[1]))
			{
				$uuids = [];
				global $players;
				foreach($players as $uuid => $player)
				{
					if(stristr($player["name"], $args[1]))
					{
						$uuids[$player["name"]] = $uuid;
						$username = $player["name"];
					}
				}
				if(count($uuids) == 0)
				{
					$ui->add("Couldn't find ".$args[1]);
				}
				else if(count($uuids) > 1)
				{
					$ui->add("Ambiguous name; found: ".join(", ", array_keys($uuids)));
				}
				else
				{
					global $followEntity, $entities;
					$followEntity = false;
					$uuid = $uuids[$username];
					foreach($entities as $eid => $entity)
					{
						if($entity["uuid"] == $uuid)
						{
							$followEntity = $eid;
						}
					}
					if($followEntity === false)
					{
						$ui->add("Couldn't find {$username}'s entity");
					}
					else
					{
						$ui->add("Understood.");
					}
				}
			}
			else
			{
				$ui->add("Syntax: .follow <name>");
			}
			break;

			case "unfollow":
			global $followEntity;
			$followEntity = false;
			$ui->add("Done.");
			break;

			case "slot":
			$slot = 0;
			if(isset($args[1]))
			{
				$slot = intval($args[1]);
			}
			if($slot < 1 || $slot > 9)
			{
				$ui->add("Syntax: .slot <1-9>");
				break;
			}
			$con->startPacket("held_item_change");
			$con->writeShort($slot - 1);
			$con->send();
			$ui->add("Done.");
			break;

			case "reload":
			autoloadPlugins();
			break;

			case "reconnect":
			global $reconnect;
			$reconnect = true;
			break;

			case "quit":
			case "disconnect":
			global $options;
			$options["noreconnect"] = true;
			$con->close();
			break;

			default:
			$ui->add("Unknown command '.".$args[0]."' -- use '.help' for a list of commands.");
		}
	}
	if($send)
	{
		global $con;
		$con->startPacket("serverbound_chat_message");
		$con->writeString($msg);
		$con->send();
	}
}
$ui->tabcomplete_function = function($word)
{
	global $players;
	$word = strtolower($word);
	$completions = [];
	$len = strlen($word);
	foreach($players as $player)
	{
		if(strtolower(substr($player["name"], 0, $len)) == $word)
		{
			array_push($completions, $player["name"]);
		}
	}
	return $completions;
};
do
{
	$ui->add("Connecting using {$minecraft_version}... ")->render();
	$stream = fsockopen($serverarr[0], $serverarr[1], $errno, $errstr, 3) or die($errstr."\n");
	$con = new \Phpcraft\ServerConnection($stream, $protocol_version);
	$con->sendHandshake($serverarr[0], $serverarr[1], 2);
	$ui->append("Connection established.")->add("Logging in...")->render();
	if($error = $con->login($account, $translations))
	{
		$ui->add($error)->render();
		exit;
	}
	$ui->input_prefix = "<{$account->getUsername()}> ";
	$ui->append(" Success!")->render();
	$ui->add("");
	$reconnect = false;
	$players = [];
	$x = 0;
	$y = 0;
	$z = 0;
	$yaw = 0;
	$pitch = 0;
	$_x = 0;
	$_y = 0;
	$_z = 0;
	$_yaw = 0;
	$_pitch = 0;
	$motion_x = 0;
	$motion_y = 0;
	$motion_z = 0;
	$entityId = false;
	$entities = [];
	$followEntity = false;
	$dimension = 0;
	$next_tick = false;
	$posticks = 0;
	do
	{
		$start = microtime(true);
		while(($packet_id = $con->readPacket(0)) !== false)
		{
			if(!($packet_name = \Phpcraft\Packet::clientboundPacketIdToName($packet_id, $protocol_version)))
			{
				continue;
			}
			if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("packet", [
				"packet_name" => $packet_name,
				"connection" => $con
			])))
			{
				continue;
			}
			if($packet_name == "clientbound_chat_message")
			{
				$message = $con->readString();
				if($con->readByte() != 2) // TODO: Above Hotbar
				{
					$ui->add(\Phpcraft\Phpcraft::chatToText(json_decode($message, true), 1, $translations));
				}
			}
			else if($packet_name == "player_list_item")
			{
				$action = $con->readVarInt();
				$amount = $con->readVarInt();
				for($i = 0; $i < $amount; $i++)
				{
					$uuid = $con->readUuid()->toString();
					if($action == 0)
					{
						$username = $con->readString();
						$properties = $con->readVarInt();
						for($j = 0; $j < $properties; $j++)
						{
							$con->readString();
							$con->readString();
							if($con->readBoolean())
							{
								$con->readString();
							}
						}
						$gamemode = $con->readVarInt();
						$ping = $con->readVarInt();
						$players[$uuid] = [
							"name" => $username,
							"gamemode" => $gamemode,
							"ping" => $ping
						];
					}
					else if($action == 1)
					{
						if(isset($players[$uuid]))
						{
							$players[$uuid]["gamemode"] = $con->readVarInt();
						}
					}
					else if($action == 2)
					{
						if(isset($players[$uuid]))
						{
							$players[$uuid]["ping"] = $con->readVarInt();
						}
					}
					else if($action == 4)
					{
						unset($players[$uuid]);
					}
				}
			}
			else if($packet_name == "spawn_player")
			{
				$eid = $con->readVarInt();
				if($eid != $entityId)
				{
					if($protocol_version > 47)
					{
						$entities[$eid] = [
							"uuid" => $con->readUuid()->toString(),
							"x" => $con->readDouble(),
							"y" => $con->readDouble(),
							"z" => $con->readDouble(),
							"yaw" => $con->readByte(),
							"pitch" => $con->readByte()
						];
					}
					else
					{
						$entities[$eid] = [
							"uuid" => $con->readUuid()->toString(),
							"x" => $con->readInt() / 32,
							"y" => $con->readInt() / 32,
							"z" => $con->readInt() / 32,
							"yaw" => $con->readByte(),
							"pitch" => $con->readByte()
						];
					}
				}
			}
			else if($packet_name == "entity_look_and_relative_move")
			{
				$eid = $con->readVarInt();
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += ($con->readShort(true) / 4096);
						$entities[$eid]["y"] += ($con->readShort(true) / 4096);
						$entities[$eid]["z"] += ($con->readShort(true) / 4096);
					}
					else
					{
						$entities[$eid]["x"] += ($con->readByte(true) / 32);
						$entities[$eid]["y"] += ($con->readByte(true) / 32);
						$entities[$eid]["z"] += ($con->readByte(true) / 32);
					}
					$entities[$eid]["yaw"] = $con->readByte();
					$entities[$eid]["pitch"] = $con->readByte();
				}
			}
			else if($packet_name == "entity_relative_move")
			{
				$eid = $con->readVarInt();
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += ($con->readShort(true) / 4096);
						$entities[$eid]["y"] += ($con->readShort(true) / 4096);
						$entities[$eid]["z"] += ($con->readShort(true) / 4096);
					}
					else
					{
						$entities[$eid]["x"] += ($con->readByte(true) / 32);
						$entities[$eid]["y"] += ($con->readByte(true) / 32);
						$entities[$eid]["z"] += ($con->readByte(true) / 32);
					}
				}
			}
			else if($packet_name == "entity_look")
			{
				$eid = $con->readVarInt();
				if(isset($entities[$eid]))
				{
					$entities[$eid]["yaw"] = $con->readByte();
					$entities[$eid]["pitch"] = $con->readByte();
				}
			}
			else if($packet_name == "entity_teleport")
			{
				$eid = $con->readVarInt();
				if(isset($entities[$eid]))
				{
					if($eid != $entityId && isset($entities[$eid]))
					{
						if($protocol_version > 47)
						{
							$entities[$eid]["x"] = $con->readDouble();
							$entities[$eid]["y"] = $con->readDouble();
							$entities[$eid]["z"] = $con->readDouble();
						}
						else
						{
							$entities[$eid]["x"] = $con->readInt() / 32;
							$entities[$eid]["y"] = $con->readInt() / 32;
							$entities[$eid]["z"] = $con->readInt() / 32;
						}
						$entities[$eid]["yaw"] = $con->readByte();
						$entities[$eid]["pitch"] = $con->readByte();
					}
				}
			}
			else if($packet_name == "destroy_entites")
			{
				$count = $con->readVarInt();
				for($i = 0; $i < $count; $i++)
				{
					$eid = $con->readVarInt();
					if(isset($entities[$eid]))
					{
						if($followEntity === $eid)
						{
							$ui->add("The entity I was following has been destroyed.");
							$followEntity = false;
						}
						unset($entities[$eid]);
					}
				}
			}
			else if($packet_name == "keep_alive_request")
			{
				\Phpcraft\KeepAliveRequestPacket::read($con)->getResponse()->send($con);
			}
			else if($packet_name == "teleport")
			{
				$x_ = $con->readDouble();
				$y_ = $con->readDouble();
				$z_ = $con->readDouble();
				$yaw_ = $con->readFloat();
				$pitch_ = $con->readFloat();
				$flags = strrev(decbin($con->readByte()));
				if(strlen($flags) < 5)
				{
					$flags .= str_repeat("0", 5 - strlen($flags));
				}
				if(substr($flags, 0, 1) == "1")
				{
					$x += $x_;
				}
				else
				{
					$x = $x_;
				}
				if(substr($flags, 1, 1) == "1")
				{
					$y += $y_;
				}
				else
				{
					$y = $y_;
				}
				if(substr($flags, 2, 1) == "1")
				{
					$z += $z_;
				}
				else
				{
					$z = $z_;
				}
				if(substr($flags, 3, 1) == "1")
				{
					$yaw += $yaw_;
				}
				else
				{
					$yaw = $yaw_;
				}
				if(substr($flags, 4, 1) == "1")
				{
					$pitch += $pitch_;
				}
				else
				{
					$pitch = $pitch_;
				}
				if($protocol_version > 47)
				{
					$con->startPacket("teleport_confirm");
					$con->writeVarInt($con->readVarInt());
					$con->send();
				}
			}
			else if($packet_name == "update_health")
			{
				if($con->readFloat() < 1)
				{
					$con->startPacket("client_status");
					$con->writeVarInt(0); // Respawn
					$con->send();
				}
			}
			else if($packet_name == "open_window")
			{
				$con->startPacket("close_window");
				$con->writeByte($con->readByte());
				$con->send();
			}
			else if($packet_name == "join_game")
			{
				$next_tick = microtime(true);
				$entityId = $con->readInt();
				$con->ignoreBytes(1);
				if($protocol_version > 47)
				{
					$dimension = $con->readInt();
				}
				else
				{
					$dimension = $con->readByte();
				}
				$con->startPacket("send_plugin_message");
				$con->writeString($protocol_version > 340 ? "minecraft:brand" : "MC|Brand");
				$con->writeString("Phpcraft");
				$con->send();
				$con->startPacket("client_settings");
				$con->writeString($options["lang"]);
				$con->writeByte(16); // View Distance
				$con->writeVarInt(0); // Chat Mode (0 = all)
				$con->writeBoolean(true); // Chat colors
				$con->writeByte(0x7F); // Displayed Skin Parts (7F = all)
				if($protocol_version > 47)
				{
					$con->writeVarInt(1); // Main Hand (0 = left, 1 = right)
				}
				$con->send();
				if(isset($options["joinmsg"]))
				{
					handleConsoleMessage($options["joinmsg"]);
				}
			}
			else if($packet_name == "respawn")
			{
				if($protocol_version > 47)
				{
					$dimension = $con->readInt();
				}
				else
				{
					$dimension = $con->readByte();
				}
			}
			else if($packet_name == "change_game_state")
			{
				if($con->readByte() == 7 && $con->readFloat() > 1)
				{
					$ui->add("The server just sent a packet that would crash a vanilla client.")->render();
				}
			}
			else if($packet_name == "disconnect")
			{
				$ui->add("Server closed connection: ".\Phpcraft\Phpcraft::chatToText($con->readString(), 1))->render();
				$reconnect = !isset($options["noreconnect"]);
				$next_tick = microtime(true) + 10;
			}
		}
		while($message = $ui->render(true))
		{
			handleConsoleMessage($message);
		}
		$time = microtime(true);
		if($next_tick)
		{
			while($next_tick <= $time) // executed for every 50 ms
			{
				if($followEntity !== false)
				{
					$motion_x = ($entities[$followEntity]["x"] - $x);
					$motion_y = ($entities[$followEntity]["y"] - $y);
					$motion_z = ($entities[$followEntity]["z"] - $z);
					$yaw = $entities[$followEntity]["yaw"] / 256 * 360;
					$pitch = $entities[$followEntity]["pitch"] / 256 * 360;
				}
				$motion_speed = 0.35; // max. blocks per tick
				if($motion_x > 0)
				{
					if($motion_x < $motion_speed)
					{
						$x += $motion_x;
						$motion_x = 0;
					}
					else
					{
						$x += $motion_speed;
						$motion_x -= $motion_speed;
					}
				}
				else if($motion_x < 0)
				{
					if($motion_x > -$motion_speed)
					{
						$x += $motion_x;
						$motion_x = 0;
					}
					else
					{
						$x -= $motion_speed;
						$motion_x += $motion_speed;
					}
				}
				if($motion_y > 0)
				{
					$onGround = false;
					if($motion_y < $motion_speed)
					{
						$y += $motion_y;
						$motion_y = 0;
					}
					else
					{
						$y += $motion_speed;
						$motion_y -= $motion_speed;
					}
					$onGround = false;
				}
				else if($motion_y < 0)
				{
					$onGround = false;
					if($motion_y > -$motion_speed)
					{
						$y += $motion_y;
						$motion_y = 0;
					}
					else
					{
						$y -= $motion_speed;
						$motion_y += $motion_speed;
					}
					$onGround = false;
				}
				else
				{
					$onGround = fmod($y, 1) == 0;
				}
				if($motion_z > 0)
				{
					if($motion_z < $motion_speed)
					{
						$z += $motion_z;
						$motion_z = 0;
					}
					else
					{
						$z += $motion_speed;
						$motion_z -= $motion_speed;
					}
				}
				else if($motion_z < 0)
				{
					if($motion_z > -$motion_speed)
					{
						$z += $motion_z;
						$motion_z = 0;
					}
					else
					{
						$z -= $motion_speed;
						$motion_z += $motion_speed;
					}
				}
				$poschange = ($x != $_x || $y != $_y || $z != $_z);
				$rotchange = ($yaw != $_yaw || $pitch != $_pitch);
				if($poschange)
				{
					if($rotchange)
					{
						$con->startPacket("player_position_and_look");
						$con->writeDouble($x);
						$con->writeDouble($y);
						$con->writeDouble($z);
						$con->writeFloat($yaw);
						$con->writeFloat($pitch);
						$con->writeBoolean($onGround);
						$con->send();
						$_yaw = $yaw;
						$_pitch = $pitch;
					}
					else
					{
						$con->startPacket("player_position");
						$con->writeDouble($x);
						$con->writeDouble($y);
						$con->writeDouble($z);
						$con->writeBoolean($onGround);
						$con->send();
					}
					$_x = $x;
					$_y = $y;
					$_z = $z;
					if($posticks > 0)
					{
						$posticks = 0;
					}
				}
				else if($rotchange)
				{
					$con->startPacket("player_look");
					$con->writeFloat($yaw);
					$con->writeFloat($pitch);
					$con->writeBoolean($onGround);
					$con->send();
					$_yaw = $yaw;
					$_pitch = $pitch;
					if($posticks > 0)
					{
						$posticks = 0;
					}
				}
				else if($protocol_version <= 47 || ++$posticks == 20)
				{
					$con->startPacket("player");
					$con->writeBoolean($onGround);
					$con->send();
				}
				$time = microtime(true);
				$next_tick = ($time + 0.05 - ($time - $next_tick));
			}
		}
		if(($remaining = (0.020 - ($time - $start))) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
		{
			time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
		}
	}
	while(!$reconnect && $con->isOpen());
}
while($reconnect || !isset($options["noreconnect"]));
