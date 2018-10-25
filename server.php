<?php
echo "\033[0;97;40mPHP Minecraft Server\nhttps://github.com/timmyrs/Phpcraft\n";
require __DIR__."/Phpcraft.php";

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

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

		case "port":
		$options[$n] = $v;
		break;

		case "?":
		case "help":
		echo "online=<on/off>  set online or offline mode\n";
		echo "port=<port>      bind to port <port>\n";
		exit;

		default:
		die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}
$online_mode = true;

if(!isset($options["online"]))
{
	$options["online"] = true;
}
if($options["online"])
{
	if($extensions_needed = \Phpcraft\Utils::getExtensionsMissingToGoOnline())
	{
		die("To host an online server, you need ".join(" and ", $extensions_needed).".\nCheck your php.ini, use apt-get install or set online=off.\n");
	}
	echo "Generating 1024-bit RSA keypair...";
	$private_key = openssl_pkey_new([
		"private_key_bits" => 1024,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	]);
	echo " Done.\n";
}
if(empty($options["port"]))
{
	$options["port"] = 25565;
}
if(stristr(PHP_OS, "WIN") && !stristr(PHP_OS, "DAR"))
{
	echo "Press enter to acknowledge the following and start the server:\n";
	echo "- Since you're on Windows, you shouldn't unfocus this window.\n"; // https://bugs.php.net/bug.php?id=34972
	echo "- If you're using Windows 8.1 or below, you won't see any colors.\n";
	fgets($stdin);
}
echo "Binding to port ".$options["port"]."...";
$server = stream_socket_server("tcp://0.0.0.0:".$options["port"], $errno, $errstr) or die(" {$errstr}\n");
stream_set_blocking($server, false);
echo " Success.\n";
stream_set_blocking($stdin, false);
$clients = [];
function joinSuccess($i)
{
	global $clients;
	$con = $clients[$i]["connection"];
	$con->startPacket("join_game");
	$con->writeInt(1); // Entity ID
	$con->writeByte(1); // Gamemode
	if($con->getProtocolVersion() > 107) // Dimension
	{
		$con->writeInt(0);
	}
	else
	{
		$con->writeByte(0);
	}
	$con->writeByte(0); // Difficulty
	$con->writeByte(100); // Max Players
	$con->writeString("default"); // Level Type
	$con->writeBoolean(false); // Reduced Debug Info
	$con->send();
	$con->startPacket("plugin_message");
	$con->writeString($con->getProtocolVersion() > 340 ? "minecraft:brand" : "MC|Brand");
	$con->writeString("Phpcraft");
	$con->send();
	$con->startPacket("spawn_position");
	$con->writePosition(0, 100, 0);
	$con->send();
	$con->startPacket("teleport");
	$con->writeDouble(0);
	$con->writeDouble(100);
	$con->writeDouble(0);
	$con->writeFloat(0);
	$con->writeFloat(0);
	$con->writeByte(0);
	if($con->getProtocolVersion() > 47)
	{
		$con->writeVarInt(0); // Teleport ID
	}
	$con->send();
	$con->startPacket("time_update");
	$con->writeLong(0); // World Age
	$con->writeLong(-6000); // Time of Day
	$con->send();
	$con->startPacket("player_list_header_and_footer");
	$con->writeString('{"text":"Phpcraft Server"}');
	$con->writeString('{"text":"github.com/timmyrs/Phpcraft"}');
	$con->send();
	$con->startPacket("chat_message");
	$con->writeString('{"text":"Welcome to this Phpcraft server."}');
	$con->writeByte(1);
	$con->send();
	$con->startPacket("chat_message");
	$con->writeString('{"text":"You can chat with other players here. That\'s it."}');
	$con->writeByte(1);
	$con->send();
	$clients[$i]["next_heartbeat"] = microtime(true) + 15;
	$msg = [
		"color" => "yellow",
		"translate" => "multiplayer.player.joined",
		"with" => [
			[
				"text" => $clients[$i]["name"]
			]
		]
	];
	echo \Phpcraft\Utils::chatToANSIText($msg)."\n";
	$msg = json_encode($msg);
	foreach($clients as $c)
	{
		if($c["connection"]->getState() == 3 && $c["connection"]->isOpen())
		{
			try
			{
				$c["connection"]->startPacket("chat_message");
				$c["connection"]->writeString($msg);
				$c["connection"]->writeByte(1);
				$c["connection"]->send();
			}
			catch(Exception $ignored){}
		}
	}
}
do
{
	$start = microtime(true);
	foreach($clients as $i => $_)
	{
		$con = $clients[$i]["connection"];
		if($con->isOpen())
		{
			while(($id = $con->readPacket(false)) !== false)
			{
				if($con->getState() == 3) // Playing
				{
					$packet_name = \Phpcraft\Packet::serverboundPacketIdToName($id, $con->getProtocolVersion());
					if($packet_name == "keep_alive_response")
					{
						$clients[$i]["next_heartbeat"] = microtime(true) + 15;
						$clients[$i]["disconnect_after"] = 0;
					}
					else if($packet_name == "send_chat_message")
					{
						$msg = [
							"translate" => "chat.type.text",
							"with" => [
								[
									"text" => $clients[$i]["name"]
								],
								[
									"text" => $con->readString()
								]
							]
						];
						echo \Phpcraft\Utils::chatToANSIText($msg)."\n";
						$msg = json_encode($msg);
						foreach($clients as $c)
						{
							if($c["connection"]->getState() == 3 && $c["connection"]->isOpen())
							{
								try
								{
									$c["connection"]->startPacket("chat_message");
									$c["connection"]->writeString($msg);
									$c["connection"]->writeByte(1);
									$c["connection"]->send();
								}
								catch(Exception $ignored){}
							}
						}
					}
				}
				else if($con->getState() == 2) // Login
				{
					if($id == 0x00) // Login Start
					{
						$clients[$i]["name"] = $con->readString();
						if(\Phpcraft\Utils::validateName($clients[$i]["name"]))
						{
							if($options["online"])
							{
								$con->sendEncryptionRequest($private_key);
							}
							else
							{
								$con->finishLogin(\Phpcraft\Utils::generateUUIDv4(true), $clients[$i]["name"]);
								joinSuccess($i);
							}
						}
						else
						{
							$clients[$i]["disconnect_after"] = microtime(true);
							break;
						}
					}
					else if($id == 0x01 && isset($clients[$i]["name"])) // Encryption Response
					{
						if($json = $con->handleEncryptionResponse($clients[$i]["name"], $private_key))
						{
							$con->finishLogin(\Phpcraft\Utils::addHypensToUUID($json["id"]), $json["name"]);
							joinSuccess($i);
						}
					}
					else
					{
						$clients[$i]["disconnect_after"] = microtime(true);
						break;
					}
				}
				else // Can only be 1; Status
				{
					if($id == 0x00)
					{
						$con->writeVarInt(0x00);
						$con->writeString('{"version":{"name":"Phpcraft","protocol":'.(\Phpcraft\Utils::isProtocolVersionSupported($con->getProtocolVersion())?$con->getProtocolVersion():69).'},"description":{"text":"A Phpcraft Server"}}');
						$con->send();
					}
					else if($id == 0x01)
					{
						$con->writeVarInt(0x01);
						$con->writeLong($con->readLong());
						$con->send();
						$clients[$i]["disconnect_after"] = microtime(true);
						break;
					}
				}
			}
			if($clients[$i]["disconnect_after"] != 0 && $clients[$i]["disconnect_after"] <= microtime(true))
			{
				$con->close();
				continue;
			}
			else if($clients[$i]["next_heartbeat"] != 0 && $clients[$i]["next_heartbeat"] <= microtime(true))
			{
				(new \Phpcraft\KeepAliveRequestPacket(time()))->send($con);
				$clients[$i]["next_heartbeat"] = 0;
				$clients[$i]["disconnect_after"] = microtime(true) + 30;
			}
		}
		else
		{
			if($con->getState() == 3)
			{
				$msg = [
					"color" => "yellow",
					"translate" => "multiplayer.player.left",
					"with" => [
						[
							"text" => $clients[$i]["name"]
						]
					]
				];
				unset($clients[$i]);
				echo \Phpcraft\Utils::chatToANSIText($msg)."\n";
				$msg = json_encode($msg);
				foreach($clients as $c)
				{
					if($c["connection"]->getState() == 3 && $c["connection"]->isOpen())
					{
						try
						{
							$c["connection"]->startPacket("chat_message");
							$c["connection"]->writeString($msg);
							$c["connection"]->writeByte(1);
							$c["connection"]->send();
						}
						catch(Exception $ignored){}
					}
				}
			}
			else
			{
				unset($clients[$i]);
			}
		}
	}
	$streams = [$server, $stdin];
	$null = null;
	if(stream_select($streams, $null, $null, 0) > 0)
	{
		if(in_array($server, $streams))
		{
			while(($stream = @stream_socket_accept($server, 0)) !== false)
			{
				$con = new \Phpcraft\ClientConnection($stream);
				if($con->isOpen())
				{
					if($con->getState() == 1)
					{
						array_push($clients, [
							"connection" => $con,
							"next_heartbeat" => 0,
							"disconnect_after" => microtime(true) + 10
						]);
					}
					else
					{
						array_push($clients, [
							"connection" => $con,
							"next_heartbeat" => 0,
							"disconnect_after" => 0
						]);
					}
				}
			}
		}
		if(in_array($stdin, $streams))
		{
			if($msg = trim(fgets($stdin)))
			{
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
				echo \Phpcraft\Utils::chatToANSIText($msg)."\n";
				$msg = json_encode($msg);
				foreach($clients as $c)
				{
					if($c["connection"]->getState() == 3 && $c["connection"]->isOpen())
					{
						try
						{
							$c["connection"]->startPacket("chat_message");
							$c["connection"]->writeString($msg);
							$c["connection"]->writeByte(1);
							$c["connection"]->send();
						}
						catch(Exception $ignored){}
					}
				}
			}
		}
	}
	$elapsed = (microtime(true) - $start);
	if(($remaining = (0.02 - $elapsed)) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
	{
		time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
	}
}
while(true);
