<?php
/** @noinspection PhpUnhandledExceptionInspection */
echo "Phpcraft PHP Minecraft Client\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php client.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{Account, AssetsManager, Event\ClientConsoleEvent, Event\ClientJoinEvent, Event\ClientPacketEvent, FancyUserInterface, Packet\ClientboundPacket, Packet\KeepAliveRequestPacket, Packet\ServerboundBrandPluginMessagePacket, Phpcraft, PluginManager, Position, ServerConnection, UserInterface, Versions};
if(Phpcraft::isWindows() && !in_array("help", $argv))
{
	die("I'm sorry, due to a bug in PHP's Windows port <https://bugs.php.net/bug.php?id=34972>, you'll have to use the Windows Subsystem for Linux <https://aka.ms/wslinstall> to use the PHP Minecraft Client.\n");
}
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
			else
			{
				die("Value for argument '{$n}' has to be either 'on' or 'off'.\n");
			}
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
$am = AssetsManager::fromMinecraftVersion(Versions::minecraft()[0]);
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
$stdin = fopen("php://stdin", "r") or die("Failed to open php://stdin\n");
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
		if(!Phpcraft::validateName($name))
		{
			echo "Invalid name.\n";
			$name = "";
		}
	}
}
$account = new Account($name);
if($online && !$account->loginUsingProfiles())
{
	do
	{
		readline_callback_handler_install("What's your account password? (hidden) ", function()
		{
		});
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
$ui = (isset($options["plain"]) ? new UserInterface() : new FancyUserInterface("PHP Minecraft Client", "github.com/timmyrs/Phpcraft"));
$ui->add("Resolving... ")
   ->render();
$server = Phpcraft::resolve($server);
$serverarr = explode(":", $server);
if(count($serverarr) != 2)
{
	$ui->append("Failed to resolve name. Got {$server}")
	   ->render();
	exit;
}
$ui->append("Resolved to {$server}")
   ->render();
if(empty($options["version"]))
{
	$info = Phpcraft::getServerStatus($serverarr[0], intval($serverarr[1]), 3, 1);
	if(empty($info) || empty($info["version"]) || empty($info["version"]["protocol"]))
	{
		$ui->add("Invalid status: ".json_encode($info))
		   ->render();
		exit;
	}
	$protocol_version = $info["version"]["protocol"];
	if(!($minecraft_versions = Versions::protocolToMinecraft($protocol_version)))
	{
		$ui->add("This server uses an unknown protocol version: {$protocol_version}")
		   ->render();
		exit;
	}
	$minecraft_version = $minecraft_versions[0];
}
else
{
	$minecraft_version = $options["version"];
	$protocol_version = Versions::minecraftToProtocol($minecraft_version);
	if($protocol_version === null)
	{
		$ui->add("Unknown Minecraft version: {$minecraft_version}")
		   ->render();
		exit;
	}
}
$ui->add("Preparing cache... ")
   ->render();
Phpcraft::populateCache();
$ui->append("Done.")
   ->render();
function loadPlugins()
{
	global $ui;
	echo "Loading plugins...\n";
	PluginManager::$loaded_plugins = [];
	PluginManager::loadPlugins();
	echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
	$ui->render();
}

loadPlugins();
function handleConsoleMessage(string $msg)
{
	if($msg == "")
	{
		return;
	}
	global $con;
	assert($con instanceof ServerConnection);
	if(PluginManager::fire(new ClientConsoleEvent($con, $msg)))
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
					$con->writePosition(new Position($x, $y, $z));
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
					$username = null;
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
					if($username == null)
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
				loadPlugins();
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

$ui->tabcomplete_function = function(string $word)
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
	$ui->add("Connecting using {$minecraft_version}... ")
	   ->render();
	$stream = fsockopen($serverarr[0], intval($serverarr[1]), $errno, $errstr, 3) or die($errstr."\n");
	$con = new ServerConnection($stream, $protocol_version);
	$con->sendHandshake($serverarr[0], intval($serverarr[1]), 2);
	$ui->append("Connection established.")
	   ->add("Logging in... ")
	   ->render();
	if($error = $con->login($account, $translations))
	{
		$ui->add($error)
		   ->render();
		exit;
	}
	$ui->input_prefix = "<{$account->username}> ";
	$ui->append("Success!")
	   ->render();
	PluginManager::fire(new ClientJoinEvent($con));
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
			if(!($packet_name = @ClientboundPacket::getById($packet_id, $protocol_version)->name))
			{
				continue;
			}
			if(PluginManager::fire(new ClientPacketEvent($con, $packet_name)))
			{
				continue;
			}
			if($packet_name == "clientbound_chat_message")
			{
				$message = $con->readString();
				if($con->readByte() != 2)
				{
					$ui->add(Phpcraft::chatToText(json_decode($message, true), 1, $translations));
				}
			}
			else if($packet_name == "player_info")
			{
				$action = gmp_intval($con->readVarInt());
				$amount = gmp_intval($con->readVarInt());
				for($i = 0; $i < $amount; $i++)
				{
					$uuid = $con->readUuid()
								->__toString();
					if($action == 0)
					{
						$username = $con->readString();
						$properties = gmp_intval($con->readVarInt());
						for($j = 0; $j < $properties; $j++)
						{
							$con->readString();
							$con->readString();
							if($con->readBoolean())
							{
								$con->readString();
							}
						}
						$gamemode = gmp_intval($con->readVarInt());
						$ping = gmp_intval($con->readVarInt());
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
							$players[$uuid]["gamemode"] = gmp_intval($con->readVarInt());
						}
					}
					else if($action == 2)
					{
						if(isset($players[$uuid]))
						{
							$players[$uuid]["ping"] = gmp_intval($con->readVarInt());
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
				$eid = gmp_intval($con->readVarInt());
				if($eid != $entityId)
				{
					if($protocol_version > 47)
					{
						$entities[$eid] = [
							"uuid" => $con->readUuid()
										  ->__toString(),
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
							"uuid" => $con->readUuid()
										  ->__toString(),
							"x" => gmp_intval($con->readInt()) / 32,
							"y" => gmp_intval($con->readInt()) / 32,
							"z" => gmp_intval($con->readInt()) / 32,
							"yaw" => $con->readByte(),
							"pitch" => $con->readByte()
						];
					}
				}
			}
			else if($packet_name == "entity_look_and_relative_move")
			{
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += (gmp_intval($con->readShort(true)) / 4096);
						$entities[$eid]["y"] += (gmp_intval($con->readShort(true)) / 4096);
						$entities[$eid]["z"] += (gmp_intval($con->readShort(true)) / 4096);
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
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += (gmp_intval($con->readShort(true)) / 4096);
						$entities[$eid]["y"] += (gmp_intval($con->readShort(true)) / 4096);
						$entities[$eid]["z"] += (gmp_intval($con->readShort(true)) / 4096);
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
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					$entities[$eid]["yaw"] = $con->readByte();
					$entities[$eid]["pitch"] = $con->readByte();
				}
			}
			else if($packet_name == "entity_teleport")
			{
				$eid = gmp_intval($con->readVarInt());
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
							$entities[$eid]["x"] = gmp_intval($con->readInt()) / 32;
							$entities[$eid]["y"] = gmp_intval($con->readInt()) / 32;
							$entities[$eid]["z"] = gmp_intval($con->readInt()) / 32;
						}
						$entities[$eid]["yaw"] = $con->readByte();
						$entities[$eid]["pitch"] = $con->readByte();
					}
				}
			}
			else if($packet_name == "destroy_entites")
			{
				$count = gmp_intval($con->readVarInt());
				for($i = 0; $i < $count; $i++)
				{
					$eid = gmp_intval($con->readVarInt());
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
				KeepAliveRequestPacket::read($con)
									  ->getResponse()
									  ->send($con);
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
					$con->writeVarInt(gmp_intval($con->readVarInt()));
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
				$entityId = gmp_intval($con->readInt());
				$con->ignoreBytes(1);
				if($protocol_version > 47)
				{
					$dimension = gmp_intval($con->readInt());
				}
				else
				{
					$dimension = $con->readByte();
				}
				(new ServerboundBrandPluginMessagePacket("Phpcraft"))->send($con);
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
					$dimension = gmp_intval($con->readInt());
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
					$ui->add("The server just sent a packet that would crash a vanilla client.")
					   ->render();
				}
			}
			else if($packet_name == "disconnect")
			{
				$ui->add("Server closed connection: ".Phpcraft::chatToText($con->readString(), 1))
				   ->render();
				$reconnect = !isset($options["noreconnect"]);
				$next_tick = microtime(true) + 10;
			}
		}
		while($message = $ui->render(true))
		{
			handleConsoleMessage($message);
		}
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
				$con->startPacket("position_and_look");
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
				$con->startPacket("position");
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
			$con->startPacket("look");
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
			$con->startPacket("no_movement");
			$con->writeBoolean($onGround);
			$con->send();
		}
		if(($remaining = (0.050 - (microtime(true) - $start))) > 0)
		{
			time_nanosleep(0, intval($remaining * 1000000000));
		}
	}
	while(!$reconnect && $con->isOpen());
}
while($reconnect || !isset($options["noreconnect"]));
