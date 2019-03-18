<?php
echo "Phpcraft PHP Minecraft Proxy\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php proxy.php`.\n");
}
if(empty($argv[1]))
{
	die("Syntax: php proxy.php <account name>\n");
}
require "vendor/autoload.php";

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

$account = new \Phpcraft\Account($argv[1]);
if(!$account->loginUsingProfiles())
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
echo "Authenticated as ".$account->getUsername()."\n";

/*echo "Autoloading plugins...\n";
\Phpcraft\PluginManager::$platform = "phpcraft:proxy";
\Phpcraft\PluginManager::autoloadPlugins();
echo "Loaded ".count(\Phpcraft\PluginManager::$loaded_plugins)." plugin(s).\n";*/

$socket = stream_socket_server("tcp://0.0.0.0:25565", $errno, $errstr) or die($errstr."\n");
$private_key = openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
$server = new \Phpcraft\Server($socket, $private_key);
$client_con = null;
$server_con = null;
$server_eid = -1;
$server->list_ping_function = function($con)
{
	return [
		"version" => [
			"name" => "\\Phpcraft\\Server",
			"protocol" => (\Phpcraft\Phpcraft::isProtocolVersionSupported($con->protocol_version) ? $con->protocol_version : 69)
		],
		"description" => [
			"text" => "A Phpcraft Proxy"
		]
	];
};
$server->join_function = function($con)
{
	if(!\Phpcraft\Phpcraft::isProtocolVersionSupported($con->protocol_version))
	{
		$con->disconnect(["text" => "You're using an incompatible version."]);
		return;
	}
	global $account, $client_con;
	if($client_con)
	{
		echo $con->username." tried to join.\n";
		$con->disconnect(["text" => "Someone else is already using the proxy."]);
		return;
	}
	$client_con = $con;
	echo $con->username." has joined.\n";
	$packet = new \Phpcraft\JoinGamePacket();
	$packet->eid = $con->eid;
	$packet->gamemode = \Phpcraft\Gamemode::SURVIVAL;
	$packet->dimension = \Phpcraft\Dimension::OVERWORLD;
	$packet->difficulty = \Phpcraft\Difficulty::PEACEFUL;
	$packet->send($con);
	$con->startPacket("spawn_position");
	$con->writePosition(new \Phpcraft\Position(0, 100, 0));
	$con->send();
	$con->startPacket("teleport");
	$con->writeDouble(0);
	$con->writeDouble(100);
	$con->writeDouble(0);
	$con->writeFloat(0);
	$con->writeFloat(0);
	$con->writeByte(0);
	if($con->protocol_version > 47)
	{
		$con->writeVarInt(0); // Teleport ID
	}
	$con->send();
	$con->startPacket("clientbound_chat_message");
	$con->writeString('{"text":"Welcome to the Phpcraft proxy, '.$con->username.'. This proxy is authenticated as '.$account->getUsername().'. Use .connect <ip> to connect to a Minecraft server."}');
	$con->writeByte(1);
	$con->send();
};
$server->packet_function = function($con, $packet_name, $packet_id)
{
	global $server_con;
	//echo "> ".$packet_name." (".$packet_id.")\n";
	if($packet_name == "serverbound_chat_message")
	{
		$msg = $con->readString();
		if(substr($msg, 0, 1) == ".")
		{
			$arr = explode(" ", $msg);
			switch($arr[0])
			{
				case ".?":
				case ".help":
				$con->startPacket("clientbound_chat_message");
				$con->writeString(json_encode(["text" =>
					".abilities <0-F> -- set your abilities\n".
					".connect <ip> -- connect to a server\n".
					".disconnect -- disconnect from the server\n".
					".gamemode <0-3> -- set your gamemode\n".
					".metadata <0-FF> -- set your metadata\n".
					".say <msg> -- sends a chat message"
				]));
				$con->writeByte(1);
				$con->send();
				break;

				case ".abilities":
				if(count($arr) < 2)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Syntax: .abilities <0-F>","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				$con->startPacket("clientbound_abilities");
				$con->writeByte(hexdec($arr[1]));
				$con->writeFloat(0.4000000059604645);
				$con->writeFloat(0.699999988079071);
				$con->send();
				break;

				case ".connect":
				if(count($arr) < 2)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Syntax: .connect <ip>","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				if($server_con)
				{
					$server_con->close();
					$server_con = null;
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Disconnected."}');
					$con->writeByte(1);
					$con->send();
				}
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Resolving name..."}');
				$con->writeByte(1);
				$con->send();
				$server = \Phpcraft\Phpcraft::resolve($arr[1]);
				$serverarr = explode(":", $server);
				if(count($serverarr) != 2)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Error: Got '.$server.'","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Connecting to '.$server.'..."}');
				$con->writeByte(1);
				$con->send();
				$stream = fsockopen($serverarr[0], $serverarr[1], $errno, $errstr, 3);
				if(!$stream)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"'.$errstr.'","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Logging in..."}');
				$con->writeByte(1);
				$con->send();
				$server_con = new \Phpcraft\ServerConnection($stream, $con->protocol_version);
				$server_con->sendHandshake($serverarr[0], $serverarr[1], 2);
				global $account;
				if($error = $server_con->login($account))
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"'.$error.'","color":"red"}');
					$con->writeByte(1);
					$con->send();
					$server_con = null;
					break;
				}
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Connected and logged in."}');
				$con->writeByte(1);
				$con->send();
				break;

				case ".disconnect":
				if($server_con)
				{
					$server_con->close();
					$server_con = null;
				}
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Disconnected."}');
				$con->writeByte(1);
				$con->send();
				break;

				case ".gamemode":
				if(count($arr) < 2)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Syntax: .gamemode <0-3>","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				$con->startPacket("change_game_state");
				$con->writeByte(3);
				$con->writeFloat($arr[1]);
				$con->send();
				break;

				case ".metadata":
				if(count($arr) < 2)
				{
					$con->startPacket("clientbound_chat_message");
					$con->writeString('{"text":"Syntax: .metadata <0-FF>","color":"red"}');
					$con->writeByte(1);
					$con->send();
					break;
				}
				$con->startPacket("entity_metadata");
				$con->writeVarInt(-1);
				$con->writeByte(0);
				$con->writeVarInt(0);
				$con->writeByte(hexdec($arr[1]));
				$con->writeByte(0xFF);
				$con->send();
				break;

				case ".say":
				if($server_con)
				{
					$server_con->startPacket("serverbound_chat_message");
					$server_con->writeString(substr($msg, 4));
					$server_con->send();
				}
				break;

				default:
				$con->startPacket("clientbound_chat_message");
				$con->writeString('{"text":"Unknown command. Use .help for a list of commands."}');
				$con->writeByte(1);
				$con->send();
			}
		}
		else if($server_con)
		{
			$server_con->startPacket("serverbound_chat_message");
			$server_con->writeString($msg);
			$server_con->send();
		}
	}
	else if($server_con)
	{
		$server_con->write_buffer = \Phpcraft\Phpcraft::intToVarInt($packet_id).$con->read_buffer;
		$server_con->send();
	}
};
$server->disconnect_function = function($con)
{
	global $client_con;
	if($con == $client_con)
	{
		global $server_con;
		echo $client_con->username." has left.\n";
		if($server_con)
		{
			$server_con->close();
			$server_con = null;
		}
		$client_con = null;
	}
};

echo "Now waiting for someone to connect to :25565\n";
$next_tick = microtime(true) + 0.05;
do
{
	$start = microtime(true);
	$server->accept();
	$server->handle();
	try
	{
		if($server_con)
		{
			while(($packet_id = $server_con->readPacket(0)) !== false)
			{
				$packet_name = \Phpcraft\Packet::clientboundPacketIdToName($packet_id, $server_con->protocol_version);
				//echo "< ".$packet_name." (".$packet_id.")\n";
				if($packet_name == "entity_animation" || $packet_name == "entity_metadata" || $packet_name == "entity_velocity")
				{
					$client_con->startPacket($packet_name);
					$eid = $server_con->readVarInt();
					$client_con->writeVarInt($eid == $server_eid ? $client_con->eid : $eid);
					$client_con->write_buffer .= $server_con->read_buffer;
					$client_con->send();
				}
				else if($packet_name == "keep_alive_request")
				{
					\Phpcraft\KeepAliveRequestPacket::read($server_con)->getResponse()->send($server_con);
				}
				else if($packet_name == "disconnect")
				{
					$client_con->startPacket("clientbound_chat_message");
					$client_con->writeString($server_con->readString());
					$client_con->writeByte(1);
					$client_con->send();
					$server_con->close();
					$server_con = null;
					break;
				}
				else if($packet_name == "join_game")
				{
					$packet = \Phpcraft\JoinGamePacket::read($server_con);
					$server_eid = $packet->eid;
					$client_con->startPacket("change_game_state");
					$client_con->writeByte(3);
					$client_con->writeFloat($packet->gamemode);
					$client_con->send();
				}
				else
				{
					$client_con->write_buffer = \Phpcraft\Phpcraft::intToVarInt($packet_id).$server_con->read_buffer;
					$client_con->send();
				}
			}
		}
	}
	catch(Exception $e)
	{
		echo "Closing all connections: ".get_class($e)." ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
		$client_con->disconnect(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
		$client_con = null;
		$server_con->close();
		$server_con = null;
	}
	$time = microtime(true);
	/*while($next_tick <= $time) // executed for every 50 ms
	{
		\Phpcraft\PluginManager::fire(new \Phpcraft\Event("tick", [
			"client_con" => $client_con,
			"server_con" => $server_con,
		]));
		$time = microtime(true);
		$next_tick = ($time + 0.05 - ($time - $next_tick));
	}*/
	if(($remaining = (0.020 - ($time - $start))) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
	{
		time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
	}
}
while(true);
