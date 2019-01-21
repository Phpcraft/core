<?php
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php server.php`.\n");
}
require "vendor/autoload.php";
echo "PHP Minecraft Server\nhttps://github.com/timmyrs/Phpcraft\n";

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
$ui->tabcomplete_function = function($word)
{
	global $server;
	$word = strtolower($word);
	$completions = [];
	$len = strlen($word);
	foreach($server->clients as $c)
	{
		if(strtolower(substr($c->username, 0, $len)) == $word)
		{
			array_push($completions, $c->username);
		}
	}
	return $completions;
};
$server->join_function = function($con)
{
	global $ui, $server;
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
	$con->writeString("\\Phpcraft\\Server");
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
	$msg = [
		"color" => "yellow",
		"translate" => "multiplayer.player.joined",
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
		if($c->getState() == 3)
		{
			try
			{
				$c->startPacket("chat_message");
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
	if($packet_name == "send_chat_message")
	{
		$msg = $con->readString();
		if($msg == "crash me")
		{
			$con->startPacket("change_game_state");
			$con->writeByte(7);
			$con->writeFloat(1337);
			$con->send();
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
			if($c->getState() == 3)
			{
				try
				{
					$c->startPacket("chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
			}
		}
	}
};
$server->disconnect_function = function($con)
{
	global $ui, $server;
	if($con->getState() == 3)
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
			if($c->getState() == 3)
			{
				try
				{
					$c->startPacket("chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
			}
		}
	}
};
do
{
	$start = microtime(true);
	$server->accept();
	$server->handle();
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
		$ui->add(\Phpcraft\Phpcraft::chatToText($msg, true));
		$msg = json_encode($msg);
		foreach($server->clients as $c)
		{
			if($c->getState() == 3)
			{
				try
				{
					$c->startPacket("chat_message");
					$c->writeString($msg);
					$c->writeByte(1);
					$c->send();
				}
				catch(Exception $ignored){}
			}
		}
	}
	$elapsed = (microtime(true) - $start);
	if(($remaining = (0.020 - $elapsed)) > 0) // Make sure we've waited at least 20 ms before going again because otherwise we'd be polling too much
	{
		time_nanosleep(0, $remaining * 1000000000); // usleep seems to bring the CPU to 100
	}
}
while($server->isOpen());
