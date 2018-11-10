<?php
require __DIR__."/src/autoload.php";
echo "PHP Minecraft Server\nhttps://github.com/timmyrs/Phpcraft\n";

if(PHP_OS == "WINNT")
{
	die("Bare Windows is no longer supported. Please use Cygwin or similar, instead.\n");
}
if($dependencies = \Phpcraft\UserInterface::getMissingDependencies())
{
	die("To spin up the Phpcraft UI, you need ".join(", ", $dependencies).".\n");
}

$options = ["offline" => false, "port" => 25565, "nocolor" => false, "plain" => false];
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
		echo "plain        replaces the fancy user interface with a plain one\n";
		exit;

		default:
		die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}
$ui = ($options["plain"] ? new \Phpcraft\PlainUserInterface() : new \Phpcraft\UserInterface("PHP Minecraft Server", "github.com/timmyrs/Phpcraft"));
if(!$options["offline"])
{
	if($extensions_needed = \Phpcraft\Phpcraft::getExtensionsMissingToGoOnline())
	{
		die("To host an online server, you need ".join(" and ", $extensions_needed).".\nTry apt-get install or check your PHP configuration.\n");
	}
	$ui->add("Generating 1024-bit RSA keypair... ")->render();
	$private_key = openssl_pkey_new([
		"private_key_bits" => 1024,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	]);
	$ui->append("Done.")->render();
}
$ui->add("Binding to port ".$options["port"]."... ")->render();
$server = stream_socket_server("tcp://0.0.0.0:".$options["port"], $errno, $errstr) or die(" {$errstr}\n");
stream_set_blocking($server, false);
$ui->input_prefix = "[Server] ";
$ui->append("Success!")->render();
$clients = [];
$ui->tabcomplete_function = function($word)
{
	global $clients;
	$word = strtolower($word);
	$completions = [];
	$len = strlen($word);
	foreach($clients as $client)
	{
		if(strtolower(substr($client["name"], 0, $len)) == $word)
		{
			array_push($completions, $client["name"]);
		}
	}
	return $completions;
};
function joinSuccess($i)
{
	global $ui, $clients;
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
	$ui->add(\Phpcraft\Phpcraft::chatToANSIText($msg));
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
						$msg = $con->readString();
						if($options["nocolor"])
						{
							$msg = ["text" => $msg];
						}
						else
						{
							$msg = \Phpcraft\Phpcraft::textToChat($msg, true);
						}
						$msg = [
							"translate" => "chat.type.text",
							"with" => [
								[
									"text" => $clients[$i]["name"]
								],
								$msg
							]
						];
						$ui->add(\Phpcraft\Phpcraft::chatToANSIText($msg));
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
						if(\Phpcraft\Phpcraft::validateName($clients[$i]["name"]))
						{
							if($options["offline"])
							{
								$con->finishLogin(\Phpcraft\Phpcraft::generateUUIDv4(true), $clients[$i]["name"]);
								joinSuccess($i);
							}
							else
							{
								$con->sendEncryptionRequest($private_key);
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
							$con->finishLogin(\Phpcraft\Phpcraft::addHypensToUUID($json["id"]), $json["name"]);
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
						$con->writeString('{"version":{"name":"Phpcraft","protocol":'.(\Phpcraft\Phpcraft::isProtocolVersionSupported($con->getProtocolVersion())?$con->getProtocolVersion():69).'},"description":{"text":"A Phpcraft Server"}}');
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
				$ui->add(\Phpcraft\Phpcraft::chatToANSIText($msg));
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
	$read = [$server];
	$null = null;
	if(stream_select($read, $null, $null, 0))
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
	while($msg = $ui->render(false))
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
		$ui->add(\Phpcraft\Phpcraft::chatToANSIText($msg));
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
	$elapsed = (microtime(true) - $start);
	if(($remaining = (0.02 - $elapsed)) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
	{
		time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
	}
}
while(true);
