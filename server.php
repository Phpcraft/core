<?php
echo "Phpcraft PHP Minecraft Server\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php server.php`.\n");
}
require "vendor/autoload.php";

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
$ui = ($options["plain"] ? new \Phpcraft\UserInterface() : new \Phpcraft\FancyUserInterface("PHP Minecraft Server", "github.com/timmyrs/Phpcraft"));
if($options["offline"])
{
	$private_key = null;
}
else
{
	$ui->add("Generating 1024-bit RSA keypair... ")->render();
	$private_key = openssl_pkey_new([
		"private_key_bits" => 1024,
		"private_key_type" => OPENSSL_KEYTYPE_RSA,
	]);
	$ui->append("Done.")->render();
}
$ui->add("Binding to port ".$options["port"]."... ")->render();
$stream = stream_socket_server("tcp://0.0.0.0:".$options["port"], $errno, $errstr) or die(" {$errstr}\n");
$server = new \Phpcraft\Server($stream, $private_key);
$ui->input_prefix = "[Server] ";
$ui->append("Success!")->render();
echo "Autoloading plugins...\n";
\Phpcraft\PluginManager::$platform = "phpcraft:server";
\Phpcraft\PluginManager::autoloadPlugins();
echo "Loaded ".count(\Phpcraft\PluginManager::$loaded_plugins)." plugin(s).\n";
$ui->render();
$ui->tabcomplete_function = function($word)
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
$server->join_function = function($con)
{
	if(!\Phpcraft\Phpcraft::isProtocolVersionSupported($con->protocol_version))
	{
		$con->disconnect(["text" => "You're using an incompatible version."]);
		return;
	}
	global $ui, $server;
	if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("join", [
		"server" => $server,
		"client" => $con
	])))
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
	$ui->add(\Phpcraft\Phpcraft::chatToText($msg, 1));
	$msg = json_encode($msg);
	foreach($server->clients as $c)
	{
		if($c->state == 3)
		{
			try
			{
				$c->startPacket("clientbound_chat_message");
				$c->writeString($msg);
				$c->writeByte(1);
				$c->send();
			}
			catch(Exception $ignored){}
		}
	}
};
$server->packet_function = function($con, $packet_name, $packet_id)
{
	global $options, $ui, $server;
	if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("packet", [
		"server" => $server,
		"packet_name" => $packet_name,
		"client" => $con
	])))
	{
		return;
	}
	if($packet_name == "serverbound_chat_message")
	{
		$msg = $con->readString();
		if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("chat_message", [
			"message" => $msg,
			"client" => $con
		])))
		{
			return;
		}
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
					"text" => $con->username
				],
				$msg
			]
		];
		$ui->add(\Phpcraft\Phpcraft::chatToText($msg, true));
		$msg = json_encode($msg);
		foreach($server->clients as $c)
		{
			if($c->state == 3)
			{
				try
				{
					$c->startPacket("clientbound_chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
			}
		}
	}
	else if($packet_name == "player_position" || $packet_name == "player_position_and_look")
	{
		$con->pos = $con->readPrecisePosition();
	}
};
$server->disconnect_function = function($con)
{
	global $ui, $server;
	if($con->state == 3)
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
		$ui->add(\Phpcraft\Phpcraft::chatToText($msg, true));
		$msg = json_encode($msg);
		foreach($server->clients as $c)
		{
			if($c->state == 3)
			{
				try
				{
					$c->startPacket("clientbound_chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
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
		if(\Phpcraft\PluginManager::fire(new \Phpcraft\Event("console_message", [
			"server" => $server,
			"message" => $msg
		])))
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
		$ui->add(\Phpcraft\Phpcraft::chatToText($msg, true));
		$msg = json_encode($msg);
		foreach($server->clients as $c)
		{
			if($c->state == 3)
			{
				try
				{
					$c->startPacket("clientbound_chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
			}
		}
	}
	$time = microtime(true);
	$ticks = 0;
	while($next_tick <= $time) // executed for every 50 ms
	{
		\Phpcraft\PluginManager::fire(new \Phpcraft\Event("tick", [
			"server" => $server
		]));
		$time = microtime(true);
		if(++$ticks == 10)
		{
			break;
		}
		$next_tick = ($time + 0.05 - ($time - $next_tick));
	}
	if(($remaining = (0.020 - ($time - $start))) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
	{
		time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
	}
}
while($server->isOpen());
