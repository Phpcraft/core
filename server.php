<?php
/** @noinspection DuplicatedCode */
echo "Phpcraft PHP Minecraft Server\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php server.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{ClientConnection, Event\ServerChatEvent, Event\ServerConsoleEvent, Event\ServerFlyingChangeEvent, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerOnGroundChangeEvent, Event\ServerPacketEvent, Event\ServerTickEvent, FancyUserInterface, Phpcraft, PluginManager, Server, UserInterface, Versions};
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
			echo "plain        replaces the fancy user interface with a plain one\n";
			exit;
		default:
			die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}
if(Phpcraft::isWindows())
{
	echo "Some things to note about Windows: You won't be able to send messages as server or use server commands, and plain user interface is forcefully enabled.\n";
	$options["plain"] = true;
}
$ui = ($options["plain"] ? new UserInterface() : new FancyUserInterface("PHP Minecraft Server", "github.com/timmyrs/Phpcraft"));
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
$ui->input_prefix = "[Server] ";
$ui->append("Success!")
   ->add("Preparing cache... ")
   ->render();
Phpcraft::populateCache();
$ui->append("Done.")
   ->render();
echo "Loading plugins...\n";
PluginManager::loadPlugins();
echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
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
$server->list_ping_function = function(ClientConnection $con) use (&$default_list_ping_function)
{
	return $default_list_ping_function($con) + [
			"modinfo" => [
				"type" => "FML",
				"modList" => []
			]
		];
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
					$con->disconnect(["text" => "You're already on this server, and I have found no reasonable solution"]);
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
$server->packet_function = function(ClientConnection $con, $packet_name)
{
	global $options, $ui, $server;
	if(PluginManager::fire(new ServerPacketEvent($server, $con, $packet_name)))
	{
		return;
	}
	if($packet_name == "position" || $packet_name == "position_and_look" || $packet_name == "look" || $packet_name == "no_movement")
	{
		if($packet_name == "position" || $packet_name == "position_and_look")
		{
			$con->pos = $con->readPrecisePosition();
		}
		if($packet_name == "position_and_look" || $packet_name == "look")
		{
			$con->yaw = $con->readFloat();
			$con->pitch = $con->readFloat();
		}
		$_on_ground = $con->on_ground;
		$con->on_ground = $con->readBoolean();
		if($_on_ground != $con->on_ground)
		{
			PluginManager::fire(new ServerOnGroundChangeEvent($server, $con, $_on_ground));
		}
	}
	else if($packet_name == "serverbound_abilities")
	{
		$flags = $con->readByte();
		if($flags >= 0x08)
		{
			$flags -= 0x08;
		}
		if($flags >= 0x04)
		{
			$flags -= 0x04;
		}
		$_flying = $con->flying;
		$con->flying = ($flags >= 0x02);
		if($_flying != $con->flying)
		{
			PluginManager::fire(new ServerFlyingChangeEvent($server, $con, $_flying));
		}
	}
	else if($packet_name == "serverbound_chat_message")
	{
		$msg = $con->readString(256);
		if(PluginManager::fire(new ServerChatEvent($server, $con, $msg)))
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
};
$server->disconnect_function = function(ClientConnection $con)
{
	global $ui, $server;
	if($con->state == 3)
	{
		if(!PluginManager::fire(new ServerLeaveEvent($server, $con)))
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
	}
};
$next_tick = microtime(true) + 0.05;
do
{
	$start = microtime(true);
	$server->accept();
	$server->handle();
	while($msg = $ui->render(true))
	{
		if(PluginManager::fire(new ServerConsoleEvent($server, $msg)))
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
	PluginManager::fire(new ServerTickEvent($server));
	if(($remaining = (0.050 - (microtime(true) - $start))) > 0)
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while($server->isOpen());
