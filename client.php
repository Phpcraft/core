<?php /** @noinspection PhpUnhandledExceptionInspection DuplicatedCode */
echo "Phpcraft PHP Minecraft Client\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php client.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{Account, AssetsManager, Command\Command, Configuration, Connection, Event\ClientConsoleEvent, Event\ClientJoinEvent, Event\ClientPacketEvent, FancyUserInterface, Packet\ClientboundPacketId, Packet\KeepAliveRequestPacket, Packet\PluginMessage\ServerboundBrandPluginMessagePacket, Phpcraft, PlainUserInterface, PluginManager, Point3D, ServerConnection, Versions};
use hellsh\pai;
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
			echo "plain             uses the plain user interface e.g. for writing logs to a file\n";
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
$online = false;
pai::init();
if(isset($options["online"]) && $options["online"] === true)
{
	$online = true;
}
else if(!isset($options["online"]))
{
	echo "Would you like to join premium servers? (y/N) ";
	if(substr(pai::awaitLine(), 0, 1) == "y")
	{
		$online = true;
	}
}
$name = "";
$account = null;
if(isset($options["name"]))
{
	$account = new Account($options["name"]);
	if($online && !$account->loginUsingProfiles())
	{
		$account = null;
	}
}
if($account === null)
{
	if($online)
	{
		$account = Account::cliLogin();
	}
	else
	{
		do
		{
			echo "How would you like to be called in-game? [PhpcraftUser] ";
			$name = pai::getLine();
			if($name == "")
			{
				$account = new Account("PhpcraftUser");
			}
			else if(Account::validateUsername($name))
			{
				$account = new Account($name);
			}
			else
			{
				echo "Invalid username.\n";
			}
		}
		while($account === null);
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
	$server = pai::awaitLine();
	if(!$server)
	{
		$server = "localhost";
	}
}
try
{
	$ui = (isset($options["plain"]) ? new PlainUserInterface("PHP Minecraft Client") : new FancyUserInterface("PHP Minecraft Client"));
}
catch(RuntimeException $e)
{
	echo "Since you're on PHP <7.2.0 or Windows <10.0.10586, the plain user interface is forcefully enabled.\n";
	$ui = new PlainUserInterface("PHP Minecraft Client");
}
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
	$info = Phpcraft::getServerStatus($serverarr[0], intval($serverarr[1]), 3, Phpcraft::METHOD_MODERN);
	if(empty($info))
	{
		$ui->add("Failed to connect to server.")
		   ->render();
		exit;
	}
	if(empty($info["version"]) || empty($info["version"]["protocol"]))
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
function loadPlugins()
{
	global $ui;
	if(PluginManager::$loaded_plugins)
	{
		PluginManager::unloadAllPlugins();
		echo "Unloaded all plugins.\n";
		$ui->render();
	}
	echo "Loading plugins...\n";
	PluginManager::loadPlugins();
	echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
	$ui->render();
}

PluginManager::$command_prefix = ".";
loadPlugins();
function handleConsoleMessage(string $msg)
{
	if($msg == "")
	{
		return;
	}
	/**
	 * @var ServerConnection $con
	 */ global $ui, $con;
	$ui->add($msg);
	if(substr($msg, 0, 2) == "..")
	{
		$msg = substr($msg, 1);
	}
	else if(Command::handleMessage($con, $msg) || PluginManager::fire(new ClientConsoleEvent($con, $msg)))
	{
		$ui->render();
		return;
	}
	$con->startPacket("serverbound_chat_message");
	$con->writeString($msg);
	$con->send();
}

$ui->tabcomplete_function = function(string $word)
{
	// TODO: Tab-completion for commands
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
	$con->sendHandshake($serverarr[0], intval($serverarr[1]), Connection::STATE_LOGIN);
	$ui->append("Connection established.")
	   ->add("Logging in... ")
	   ->render();
	if($error = $con->login($account, $translations))
	{
		$ui->add($error)
		   ->render();
		exit;
	}
	if($ui instanceof FancyUserInterface)
	{
		$ui->setInputPrefix("<{$account->username}> ");
	}
	$ui->append("Success!")
	   ->render();
	PluginManager::fire(new ClientJoinEvent($con));
	$ui->add("");
	$reconnect = false;
	$players = [];
	$con->pos = new Point3D();
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
			$packetId = ClientboundPacketId::getById($packet_id, $protocol_version);
			if($packetId === null)
			{
				die("Invalid packet ID: $packet_id\n");
			}
			if(PluginManager::fire(new ClientPacketEvent($con, $packetId)))
			{
				echo "Plugin cancelled ".$packetId->name."\n";
				continue;
			}
			if($packetId->name == "clientbound_chat_message")
			{
				$message = $con->readString();
				if($con->readByte() != 2)
				{
					$ui->add(Phpcraft::chatToText(json_decode($message, true), Phpcraft::FORMAT_ANSI, $translations));
				}
			}
			else if($packetId->name == "player_info")
			{
				$action = gmp_intval($con->readVarInt());
				$amount = gmp_intval($con->readVarInt());
				for($i = 0; $i < $amount; $i++)
				{
					$uuid = $con->readUuid()
								->toString(false);
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
						$players[$uuid] = [
							"name" => $username,
							"gamemode" => gmp_intval($con->readVarInt()),
							"ping" => gmp_intval($con->readVarInt())
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
			else if($packetId->name == "spawn_player")
			{
				$eid = gmp_intval($con->readVarInt());
				if($eid != $entityId)
				{
					if($protocol_version > 47)
					{
						$entities[$eid] = [
							"uuid" => $con->readUuid()
										  ->toString(false),
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
										  ->toString(false),
							"x" => gmp_intval($con->readInt()) / 32,
							"y" => gmp_intval($con->readInt()) / 32,
							"z" => gmp_intval($con->readInt()) / 32,
							"yaw" => $con->readByte(),
							"pitch" => $con->readByte()
						];
					}
				}
			}
			else if($packetId->name == "entity_look_and_relative_move")
			{
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += ($con->readShort() / 4096);
						$entities[$eid]["y"] += ($con->readShort() / 4096);
						$entities[$eid]["z"] += ($con->readShort() / 4096);
					}
					else
					{
						$entities[$eid]["x"] += ($con->readByte() / 32);
						$entities[$eid]["y"] += ($con->readByte() / 32);
						$entities[$eid]["z"] += ($con->readByte() / 32);
					}
					$entities[$eid]["yaw"] = $con->readByte();
					$entities[$eid]["pitch"] = $con->readByte();
				}
			}
			else if($packetId->name == "entity_relative_move")
			{
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					if($protocol_version > 47)
					{
						$entities[$eid]["x"] += ($con->readShort() / 4096);
						$entities[$eid]["y"] += ($con->readShort() / 4096);
						$entities[$eid]["z"] += ($con->readShort() / 4096);
					}
					else
					{
						$entities[$eid]["x"] += ($con->readByte() / 32);
						$entities[$eid]["y"] += ($con->readByte() / 32);
						$entities[$eid]["z"] += ($con->readByte() / 32);
					}
				}
			}
			else if($packetId->name == "entity_look")
			{
				$eid = gmp_intval($con->readVarInt());
				if(isset($entities[$eid]))
				{
					$entities[$eid]["yaw"] = $con->readByte();
					$entities[$eid]["pitch"] = $con->readByte();
				}
			}
			else if($packetId->name == "entity_teleport")
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
			else if($packetId->name == "destroy_entites")
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
			else if($packetId->name == "keep_alive_request")
			{
				KeepAliveRequestPacket::read($con)
									  ->getResponse()
									  ->send($con);
			}
			else if($packetId->name == "teleport")
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
					$con->pos->x += $x_;
				}
				else
				{
					$con->pos->x = $x_;
				}
				if(substr($flags, 1, 1) == "1")
				{
					$con->pos->y += $y_;
				}
				else
				{
					$con->pos->y = $y_;
				}
				if(substr($flags, 2, 1) == "1")
				{
					$con->pos->z += $z_;
				}
				else
				{
					$con->pos->z = $z_;
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
			else if($packetId->name == "update_health")
			{
				if($con->readFloat() < 1)
				{
					$con->startPacket("client_command");
					$con->writeVarInt(0); // Respawn
					$con->send();
				}
			}
			else if($packetId->name == "open_window")
			{
				$con->startPacket("close_window");
				$con->writeByte($con->readByte());
				$con->send();
			}
			else if($packetId->name == "join_game")
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
			else if($packetId->name == "respawn")
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
			else if($packetId->name == "change_game_state")
			{
				if($con->readByte() == 7 && $con->readFloat() > 1)
				{
					$ui->add("The server just sent a packet that would crash a vanilla client.")
					   ->render();
				}
			}
			else if($packetId->name == "disconnect")
			{
				$ui->add("Server closed connection: ".Phpcraft::chatToText($con->readString(), Phpcraft::FORMAT_ANSI))
				   ->render();
				$reconnect = !isset($options["noreconnect"]);
				$next_tick = microtime(true) + 10;
			}
		}
		while($message = $ui->render(true))
		{
			handleConsoleMessage($message);
		}
		Configuration::handleQueue();
		if($followEntity !== false)
		{
			$motion_x = ($entities[$followEntity]["x"] - $con->pos->x);
			$motion_y = ($entities[$followEntity]["y"] - $con->pos->y);
			$motion_z = ($entities[$followEntity]["z"] - $con->pos->z);
			$yaw = $entities[$followEntity]["yaw"] / 256 * 360;
			$pitch = $entities[$followEntity]["pitch"] / 256 * 360;
		}
		$motion_speed = 0.35; // max. blocks per tick
		if($motion_x > 0)
		{
			if($motion_x < $motion_speed)
			{
				$con->pos->x += $motion_x;
				$motion_x = 0;
			}
			else
			{
				$con->pos->x += $motion_speed;
				$motion_x -= $motion_speed;
			}
		}
		else if($motion_x < 0)
		{
			if($motion_x > -$motion_speed)
			{
				$con->pos->x += $motion_x;
				$motion_x = 0;
			}
			else
			{
				$con->pos->x -= $motion_speed;
				$motion_x += $motion_speed;
			}
		}
		if($motion_y > 0)
		{
			$onGround = false;
			if($motion_y < $motion_speed)
			{
				$con->pos->y += $motion_y;
				$motion_y = 0;
			}
			else
			{
				$con->pos->y += $motion_speed;
				$motion_y -= $motion_speed;
			}
			$onGround = false;
		}
		else if($motion_y < 0)
		{
			$onGround = false;
			if($motion_y > -$motion_speed)
			{
				$con->pos->y += $motion_y;
				$motion_y = 0;
			}
			else
			{
				$con->pos->y -= $motion_speed;
				$motion_y += $motion_speed;
			}
			$onGround = false;
		}
		else
		{
			$onGround = fmod($con->pos->y, 1) == 0;
		}
		if($motion_z > 0)
		{
			if($motion_z < $motion_speed)
			{
				$con->pos->z += $motion_z;
				$motion_z = 0;
			}
			else
			{
				$con->pos->z += $motion_speed;
				$motion_z -= $motion_speed;
			}
		}
		else if($motion_z < 0)
		{
			if($motion_z > -$motion_speed)
			{
				$con->pos->z += $motion_z;
				$motion_z = 0;
			}
			else
			{
				$con->pos->z -= $motion_speed;
				$motion_z += $motion_speed;
			}
		}
		$poschange = ($con->pos->x != $_x || $con->pos->y != $_y || $con->pos->z != $_z);
		$rotchange = ($yaw != $_yaw || $pitch != $_pitch);
		if($poschange)
		{
			if($rotchange)
			{
				$con->startPacket("position_and_look");
				$con->writeDouble($con->pos->x);
				$con->writeDouble($con->pos->y);
				$con->writeDouble($con->pos->z);
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
				$con->writeDouble($con->pos->x);
				$con->writeDouble($con->pos->y);
				$con->writeDouble($con->pos->z);
				$con->writeBoolean($onGround);
				$con->send();
			}
			$_x = $con->pos->x;
			$_y = $con->pos->y;
			$_z = $con->pos->z;
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
