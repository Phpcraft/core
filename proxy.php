<?php
/** @noinspection PhpComposerExtensionStubsInspection */
echo "Phpcraft PHP Minecraft Proxy\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php proxy.php`.\n");
}
if(@$argv[1] == "help")
{
	die("Syntax: php proxy.php [account name]\n");
}
require "vendor/autoload.php";
use Phpcraft\
{Account, ClientConnection, Command\Command, Connection, Enum\Difficulty, Enum\Dimension, Enum\Gamemode, Event\ProxyClientPacketEvent, Event\ProxyServerPacketEvent, Event\ProxyTickEvent, Packet\ClientboundPacket, Packet\JoinGamePacket, Packet\KeepAliveRequestPacket, Packet\ServerboundPacket, PluginManager, Position, Server, ServerConnection, Versions};
$stdin = fopen("php://stdin", "r") or die("Failed to open php://stdin\n");
stream_set_blocking($stdin, true);
if(empty($argv[1]))
{
	$account = null;
	echo "No account name was provided, which means you can only connect to BungeeCord-compatible servers.\n";
}
else
{
	$account = new Account($argv[1]);
	$account->cliLogin($stdin);
	echo "Authenticated as ".$account->username."\n";
}
echo "Autoloading plugins...\n";
PluginManager::$command_prefix = "/proxy:";
PluginManager::loadPlugins();
echo "Loaded ".PluginManager::$loaded_plugins->count()." plugin(s).\n";
$socket = stream_socket_server("tcp://0.0.0.0:25565", $errno, $errstr) or die($errstr."\n");
$private_key = openssl_pkey_new([
	"private_key_bits" => 1024,
	"private_key_type" => OPENSSL_KEYTYPE_RSA
]);
$server = new Server($socket, $private_key);
$client_con = null;
$server_con = null;
$server_eid = -1;
$server->list_ping_function = function(ClientConnection $con)
{
	return [
		"version" => [
			"name" => "\\Phpcraft\\Server",
			"protocol" => (Versions::protocolSupported($con->protocol_version) ? $con->protocol_version : 69)
		],
		"description" => [
			"text" => "A Phpcraft Proxy"
		]
	];
};
$server->setGroups([
	"default" => [
		"allow" => "everything"
	]
]);
$server->join_function = function(ClientConnection $con)
{
	if(!Versions::protocolSupported($con->protocol_version))
	{
		$con->disconnect(["text" => "You're using an incompatible version."]);
		return;
	}
	global $account, $client_con;
	if($client_con != null)
	{
		echo $con->username." tried to join.\n";
		$con->disconnect(["text" => "Someone else is already using the proxy."]);
		return;
	}
	$client_con = $con;
	echo $con->username." has joined.\n";
	$packet = new JoinGamePacket();
	$packet->eid = $con->eid;
	$packet->gamemode = Gamemode::SURVIVAL;
	$packet->dimension = Dimension::OVERWORLD;
	$packet->difficulty = Difficulty::PEACEFUL;
	$packet->send($con);
	$con->startPacket("spawn_position");
	$con->writePosition(new Position(0, 100, 0));
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
	if($account != null)
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. This proxy is authenticated as '.$account->username.'. Use /proxy:connect <ip> to connect to a Minecraft server."}');
	}
	else
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. Use /proxy:connect <ip> <username> to connect to a BungeeCord-compatible server."}');
	}
	$con->writeByte(1);
	$con->send();
};
$server->packet_function = function(ClientConnection $con, ServerboundPacket $packetId)
{
	global $server_con;
	if(PluginManager::fire(new ProxyServerPacketEvent($con, $server_con, $packetId)))
	{
		return;
	}
	if($packetId->name == "serverbound_chat_message")
	{
		$msg = $con->readString($con->protocol_version < 314 ? 100 : 256);
		if(!Command::handleMessage($con, $msg) && $server_con instanceof ServerConnection)
		{
			$server_con->startPacket("serverbound_chat_message");
			$server_con->writeString($msg);
			$server_con->send();
		}
	}
	else if($server_con instanceof ServerConnection)
	{
		$server_con->write_buffer = Connection::varInt($packetId->getId($server_con->protocol_version)).$con->read_buffer;
		$server_con->send();
	}
};
$server->disconnect_function = function(ClientConnection $con)
{
	global $client_con;
	if($con === $client_con)
	{
		global $server_con;
		echo $con->username." has left.\n";
		if($server_con instanceof ServerConnection)
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
		if($server_con instanceof ServerConnection && $client_con instanceof ClientConnection)
		{
			while(($packet_id = $server_con->readPacket(0)) !== false)
			{
				$packetId = ClientboundPacket::getById($packet_id, $server_con->protocol_version);
				if(PluginManager::fire(new ProxyClientPacketEvent($client_con, $server_con, $packetId)))
				{
					continue;
				}
				if(in_array($packetId->name, [
					"entity_animation",
					"entity_effect",
					"entity_metadata",
					"entity_velocity"
				]))
				{
					$client_con->startPacket($packetId->name);
					$eid = $server_con->readVarInt();
					$client_con->writeVarInt($eid == $server_eid ? $client_con->eid : $eid);
					$client_con->write_buffer .= $server_con->read_buffer;
					$client_con->send();
				}
				else if($packetId->name == "keep_alive_request")
				{
					KeepAliveRequestPacket::read($server_con)
										  ->getResponse()
										  ->send($server_con);
				}
				else if($packetId->name == "disconnect")
				{
					$client_con->startPacket("clientbound_chat_message");
					$client_con->writeString($server_con->readString());
					$client_con->writeByte(1);
					$client_con->send();
					$server_con->close();
					$server_con = null;
					break;
				}
				else if($packetId->name == "join_game")
				{
					$packet = JoinGamePacket::read($server_con);
					$server_eid = $packet->eid;
					$client_con->startPacket("change_game_state");
					$client_con->writeByte(3);
					$client_con->writeFloat($packet->gamemode);
					$client_con->send();
				}
				else
				{
					$client_con->write_buffer = Connection::varInt($packet_id).$server_con->read_buffer;
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
	PluginManager::fire(new ProxyTickEvent($client_con, $server_con));
	if(($remaining = (0.050 - ($time - $start))) > 0)
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while(true);
