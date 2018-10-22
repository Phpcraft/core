<?php
echo "\033[0;97;40mPHP Minecraft Server\nhttps://github.com/timmyrs/Phpcraft\n";
require __DIR__."/Phpcraft.php";

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

if(stristr(PHP_OS, "WIN"))
{
	echo "Press enter to acknowledge the following and start the server:\n";
	echo "- Since you're on Windows, you shouldn't unfocus this window.\n"; // https://bugs.php.net/bug.php?id=34972
	echo "- If you're using Windows 8.1 or below, you won't see any colors.\n";
	fgets($stdin);
}

$bindurl = "tcp://0.0.0.0:25565";
echo "Binding to {$bindurl}...";
$server = stream_socket_server($bindurl, $errno, $errstr) or die(" {$errstr}\n");
stream_set_blocking($server, false);
echo " Success.\n";
stream_set_blocking($stdin, false);
$clients = [];
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
				if($clients[$i]["state"] == 3)
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
							if($c["state"] == 3 && $c["connection"]->isOpen())
							{
								$c["connection"]->startPacket("chat_message");
								$c["connection"]->writeString($msg);
								$c["connection"]->writeByte(0);
								$c["connection"]->send();
							}
						}
					}
				}
				else if($clients[$i]["state"] == 2)
				{
					if($id == 0x00)
					{
						$clients[$i]["name"] = $con->readString();
						if(\Phpcraft\Utils::validateName($clients[$i]["name"]))
						{
							// TODO: Online mode
							$con->writeVarInt(0x03);
							$con->writeVarInt(256);
							$con->send();
							$con->setCompressionThreshold(256);
							$con->writeVarInt(0x02);
							$con->writeString(\Phpcraft\Utils::generateUUIDv4(true));
							$con->writeString($clients[$i]["name"]);
							$con->send();
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
							$con->writeString($protocol_version > 340 ? "minecraft:brand" : "MC|Brand");
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
							$clients[$i]["state"] = 3;
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
								if($c["state"] == 3 && $c["connection"]->isOpen())
								{
									$c["connection"]->startPacket("chat_message");
									$c["connection"]->writeString($msg);
									$c["connection"]->writeByte(1);
									$c["connection"]->send();
								}
							}
						}
						else
						{
							$clients[$i]["disconnect_after"] = microtime(true);
							break;
						}
					}
					else
					{
						$clients[$i]["disconnect_after"] = microtime(true);
						break;
					}
				}
				else
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
				if($clients[$i]["state"] == 3)
				{
					(new \Phpcraft\KeepAliveRequestPacket(time()))->send($con);
					$clients[$i]["next_heartbeat"] = 0;
					$clients[$i]["disconnect_after"] = microtime(true) + 30;
				}
				else
				{
					$con->close();
					continue;
				}
			}
		}
		else
		{
			if($clients[$i]["state"] == 3)
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
					if($c["state"] == 3 && $c["connection"]->isOpen())
					{
						$c["connection"]->startPacket("chat_message");
						$c["connection"]->writeString($msg);
						$c["connection"]->writeByte(1);
						$c["connection"]->send();
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
				stream_set_timeout($stream, 0, 10000);
				stream_set_blocking($stream, true);
				$con = new \Phpcraft\Connection(-1, $stream);
				if($con->readPacket() !== 0x00)
				{
					@fclose($stream);
					continue;
				}
				$protocol_version = $con->readVarInt();
				$con->readString(); // hostname/ip
				$con->ignoreBytes(2); // port
				$state = $con->readVarInt();
				if($state != 1 && $state != 2)
				{
					echo "closed\n";
					@fclose($stream);
					continue;
				}
				stream_set_timeout($stream, ini_get("default_socket_timeout"));
				stream_set_blocking($stream, false);
				if($state == 1)
				{
					array_push($clients, [
						"connection" => new \Phpcraft\Connection($protocol_version, $stream),
						"state" => 1,
						"next_heartbeat" => 0,
						"disconnect_after" => microtime(true) + 10
					]);
				}
				else if($state == 2)
				{
					array_push($clients, [
						"connection" => new \Phpcraft\Connection($protocol_version, $stream),
						"state" => 2,
						"next_heartbeat" => 0,
						"disconnect_after" => 0
					]);
				}
				else
				{
					@fclose($stream);
					continue;
				}
			}
		}
		if(in_array($stdin, $streams))
		{
			$msg = trim(fgets($stdin));
			if($msg)
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
					if($c["state"] == 3)
					{
						$c["connection"]->startPacket("chat_message");
						$c["connection"]->writeString($msg);
						$c["connection"]->writeByte(0);
						$c["connection"]->send();
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
