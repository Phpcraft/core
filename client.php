<?php
echo "\033[0;97;40mPHP Minecraft Client\nhttps://github.com/timmyrs/Phpcraft\n";
require __DIR__."/Phpcraft.php";

if(stristr(PHP_OS, "LINUX"))
{
	$os = "linux";
}
else if(stristr(PHP_OS, "DAR"))
{
	$os = "mac";
}
else if(stristr(PHP_OS, "WIN"))
{
	$os = "windows";
}
else
{
	$os = "unknown";
}
if($os == "windows")
{
	$acknowledgements = [
		"Since you're on Windows, you shouldn't unfocus this window.", // https://bugs.php.net/bug.php?id=34972
		"If you're using Windows 8.1 or below, you won't see any colors."
	];
}
else
{
	$acknowledgements = [];
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
		case "acknowledge":
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
		else die("Value for argument '{$n}' has to be either 'on' or 'off'.");
		break;

		case "name":
		case "server":
		case "langfile":
		case "joinmsg":
		case "locale":
		$options[$n] = $v;
		break;

		case "?":
		case "help":
		echo "online=<on/off>  set online or offline mode\n";
		echo "name=<name>      skip name input and use <name> as name\n";
		echo "server=<server>  skip server input and connect to <server>\n";
		echo "langfile=<file>  load Minecraft translations from <file>\n";
		echo "acknowledge      automatically acknowledge all warnings\n";
		echo "joinmsg=<msg>    as soon as connected, <msg> will be handled\n";
		echo "locale=<locale>  sent to the server, default: en_US\n";
		echo "noreconnect      don't reconnect when server disconnects\n";
		exit;

		default:
		die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}

if(isset($options["langfile"]))
{
	if(!file_exists($options["langfile"]) || !is_file($options["langfile"]))
	{
		die($options["langfile"]." doesn't exist.\n");
	}
	$translations = json_decode(file_get_contents($options["langfile"]), true);
}
else
{
	array_push($acknowledgements, "No language file has been provided. Expect broken messages.");
	$translations = null;
}

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
		echo "How would you like to be called in-game? [PHPMinecraftUser] ";
		$name = trim(fgets($stdin));
		if($name == "")
		{
			$name = "PHPMinecraftUser";
			break;
		}
		if(!\Phpcraft\Utils::validateName($name))
		{
			echo "Invalid name.\n";
			$name = "";
		}
	}
}
$account = new \Phpcraft\Account($name);
if($online)
{
	if($extensions_needed = \Phpcraft\Utils::getExtensionsMissingToGoOnline())
	{
		die("To join online servers, you need ".join(" and ", $extensions_needed).".\nCheck your php.ini or use apt-get install.\n");
	}
	if(!$account->loginUsingProfiles())
	{
		do
		{
			echo "What's your account password? (visible!) ";
			$pass = trim(fgets($stdin));
			if($error = $account->login($pass))
			{
				echo $error."\n";
			}
			else break;
		}
		while(true);
	}
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
echo "Resolving...";
$server = \Phpcraft\Utils::resolve($server);
$serverarr = explode(":", $server);
if(count($serverarr) != 2)
{
	die(" Failed to resolve name. Got {$server}\n");
}
echo " Resolved to {$server}\nDetermining version...";
$con = new \Phpcraft\ServerStatusConnection($serverarr[0], $serverarr[1]);
$info = $con->getStatus();
$con->close();
if(!isset($info["version"]) || !isset($info["version"]["protocol"]))
{
	die(" Invalid response:\n".json_encode($info)."\n");
}
$protocol_version = $info["version"]["protocol"];
if(\Phpcraft\Utils::isProtocolVersionSupported($protocol_version))
{
	echo " This server is compatible!\n";
}
else
{
	die(" This server uses an unsupported protocol version ({$protocol_version}).\n");
}
$minecraft_version = \Phpcraft\Utils::getMinecraftVersionFromProtocolVersion($protocol_version);

if($acknowledgements)
{
	echo "\nPress enter to acknowledge the following and connect:\n";
	foreach($acknowledgements as $acknowledgement)
	{
		echo "- {$acknowledgement}\n";
	}
	fgets($stdin);
}

function handleConsoleMessage($msg)
{
	if($msg == "")
	{
		return;
	}
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
			echo "Yay! You've found commands, which start with a period.\n";
			echo "If you want to send a message starting with a period, use two periods.\n";
			echo "?, help           shows this help\n";
			echo "pos               returns the current position\n";
			echo "move <y>,         initates movement\n";
			echo "move <x> [y] <z>  \n";
			echo "rot <yaw> <pitch> change yaw and pitch degrees\n";
			echo "list              lists all players in the player list\n";
			echo "entities          lists all player entities\n";
			echo "follow <name>     follows <name>'s player entity\n";
			echo "unfollow          stops following whomever is being followed\n";
			echo "slot <1-9>        sets selected hotbar slot\n";
			echo "hit               swings the main hand\n";
			echo "use               uses the held item\n";
			echo "reconnect         reconnects to the server\n";
			break;

			case "pos":
			global $x, $y, $z;
			echo "$x $y $z\n";
			break;

			case "move":
			global $followEntity;
			if($followEntity !== false)
			{
				echo "\033[91mI'm currently following someone.\033[0;97;40m\n";
			}
			else
			{
				global $motion_x, $motion_y, $motion_z;
				if(isset($args[1]) && isset($args[2]) && isset($args[3]))
				{
					$motion_x += doubleval($args[1]);
					$motion_y += doubleval($args[2]);
					$motion_z += doubleval($args[3]);
					echo "Understood.\n";
				}
				else if(isset($args[1]) && isset($args[2]))
				{
					$motion_x += doubleval($args[1]);
					$motion_z += doubleval($args[2]);
					echo "Understood.\n";
				}
				else if(isset($args[1]))
				{
					$motion_y += doubleval($args[1]);
					echo "Understood.\n";
				}
				else
				{
					echo "\033[91mSyntax: .move <y>, .move <x> [y] <z>\033[0;97;40m\n";
				}
			}
			break;

			case "rot":
			global $followEntity;
			if($followEntity !== false)
			{
				echo "\033[91mI'm currently following someone.\033[0;97;40m\n";
			}
			else if(isset($args[1]) && isset($args[2]))
			{
				global $yaw, $pitch;
				$yaw = floatval($args[1]);
				$pitch = floatval($args[2]);
				echo "Understood.\n";
			}
			else
			{
				echo "\033[91mSyntax: .rot <yaw> <pitch>\033[0;97;40m\n";
			}
			break;

			case "hit":
			global $con, $protocol_version;
			$con->startPacket("animation");
			if($protocol_version > 47)
			{
				$con->writeVarInt(0);
			}
			$con->send();
			echo "Done.\n";
			break;

			case "use":
			global $con, $protocol_version, $x, $y, $z;
			if($protocol_version > 47)
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
			echo "Done.\n";
			break;

			case "list":
			$gamemodes = [
				0 => "Survival",
				1 => "Creative",
				2 => "Adventure",
				3 => "Spectator"
			];
			global $players;
			foreach($players as $player)
			{
				echo $player["name"].str_repeat(" ", 17 - strlen($player["name"])).str_repeat(" ", 5 - strlen($player["ping"])).$player["ping"]." ms  ".$gamemodes[$player["gamemode"]]." Mode\n";
			}
			break;

			case "entities":
			global $entities;
			foreach($entities as $eid => $entity)
			{
				echo $eid." ".$entity["x"]." ".$entity["y"]." ".$entity["z"]."\n";
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
					echo "\033[91mCouldn't find ".$args[1]."\033[0;97;40m\n";
				}
				else if(count($uuids) > 1)
				{
					echo "\033[91mAmbiguous name; found: ".join(", ", array_keys($uuids))."\033[0;97;40m\n";
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
						echo "\033[91mCouldn't find {$username}'s entity\033[0;97;40m\n";
					}
					else
					{
						echo "Understood.\n";
					}
				}
			}
			else
			{
				echo "\033[91mSyntax: .follow <name>\033[0;97;40m\n";
			}
			break;

			case "unfollow":
			global $followEntity;
			$followEntity = false;
			echo "Done.\n";
			break;

			case "slot":
			$slot = 0;
			if(isset($args[1]))
			{
				$slot = intval($args[1]);
			}
			if($slot < 1 || $slot > 9)
			{
				echo "\033[91mSyntax: .slot <1-9>\033[0;97;40m\n";
				break;
			}
			global $con;
			$con->startPacket("held_item_change");
			$con->writeShort($slot - 1);
			$con->send();
			echo "Done.\n";
			break;

			case "reconnect":
			global $reconnect;
			$reconnect = true;
			break;

			default:
			echo "\033[91mUnknown command '.".$args[0]."' -- use '.help' for a list of commands.\033[0;97;40m\n";
		}
	}
	if($send)
	{
		global $con;
		(new \Phpcraft\SendChatMessagePacket($msg))->send($con);
	}
}
$reconnect = false;
do
{
	echo "Connecting using {$minecraft_version} ({$protocol_version})...";
	$con = new \Phpcraft\ServerPlayConnection($protocol_version, $serverarr[0], $serverarr[1]);
	echo " Connection established.\nLogging in...";
	if($error = $con->login($account, $translations))
	{
		die(" {$error}\n");
	}
	echo " Success!\n";
	stream_set_blocking($stdin, false);
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
		while(($id = $con->readPacket(false)) !== false)
		{
			$packet_name = \Phpcraft\Packet::clientboundPacketIdToName($id, $protocol_version);
			if($packet_name === null)
			{
				continue;
			}
			if($packet_name == "chat_message")
			{
				$packet = \Phpcraft\ChatMessagePacket::read($con);
				if($packet->getPosition() != 2) // 2 = Above Hotbar
				{
					echo $packet->getMessageAsANSIText($translations)."\n";
				}
			}
			else if($packet_name == "player_list_item")
			{
				$action = $con->readVarInt();
				$amount = $con->readVarInt();
				for($i = 0; $i < $amount; $i++)
				{
					$uuid = $con->readUUIDBytes();
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
							"uuid" => $con->readUUIDBytes(),
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
							"uuid" => $con->readUUIDBytes(),
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
							echo "The entity I was following has been destroyed.\n";
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
				$con->writeString(isset($options["locale"]) ? $options["locale"] : "en_US");
				$con->writeByte(2); // View Distance
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
					echo $options["joinmsg"]."\n";
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
			else if($packet_name == "disconnect")
			{
				echo "Server closed connection: ".\Phpcraft\DisconnectPacket::read($con)->getMessageAsANSIText($translations)."\n";
				$reconnect = !isset($options["noreconnect"]);
				$next_tick = microtime(true) + 10;
			}
		}
		$streams = [$stdin];
		$null = null;
		if(stream_select($streams, $null, $null, 0) > 0)
		{
			handleConsoleMessage(trim(fgets($stdin)));
		}
		$time = microtime(true);
		if($next_tick)
		{
			while($next_tick <= $time) // executed 20 times for every second
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
				else if($protocol_version <= 47 && ++$posticks == 20)
				{
					$con->startPacket("player");
					$con->writeBoolean($onGround);
					$con->send();
				}
				$time = microtime(true);
				$next_tick = ($time + 0.05 - ($time - $next_tick));
			}
		}
		$elapsed = ($time - $start);
		if(($remaining = (0.02 - $elapsed)) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
		{
			time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
		}
	}
	while(!$reconnect && $con->isOpen());
}
while($reconnect || !isset($options["noreconnect"]));
