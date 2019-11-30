<?php
/** @noinspection PhpComposerExtensionStubsInspection */
echo "Phpcraft PHP Minecraft Proxy\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php proxy.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{Account, ClientConnection, Command\Command, Event\ProxyClientPacketEvent, Event\ProxyJoinEvent, Event\ProxyServerPacketEvent, Event\ProxyTickEvent, Packet\ClientboundPacketId, Packet\EntityPacket, Packet\JoinGamePacket, Packet\KeepAliveRequestPacket, Packet\ServerboundPacketId, PluginManager, Point3D, Server, ServerConnection, Versions};
use pas\
{pas, stdin};
echo "Would you like to provide a Mojang/Minecraft account to be possesed? [y/N] ";
stdin::init(null, false);
if(stdin::getNextLine() == "y")
{
	$account = Account::cliLogin();
	echo "Authenticated as {$account->username}.\n";
}
echo "Loading plugins...\n";
PluginManager::$command_prefix = "/proxy:";
PluginManager::loadPlugins();
echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
$socket = stream_socket_server("tcp://0.0.0.0:25565", $errno, $errstr) or die($errstr."\n");
$private_key = openssl_pkey_new([
	"private_key_bits" => 1024,
	"private_key_type" => OPENSSL_KEYTYPE_RSA
]);
$server = new Server([$socket], $private_key);
$server->compression_threshold = -1;
$client_con = null;
$server_con = null;
$server_eid = null;
$transform_packets = false;
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
	if(PluginManager::fire(new ProxyJoinEvent($con)))
	{
		$con->close();
		return;
	}
	$client_con = $con;
	echo $con->username." has joined.\n";
	(new JoinGamePacket($con->eid))->send($con);
	$con->startPacket("spawn_position");
	$con->writePosition($con->pos = new Point3D(0, 64, 0));
	$con->send();
	$con->teleport($con->pos);
	$con->startPacket("clientbound_chat_message");
	if($account instanceof Account)
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. This proxy is authenticated as '.$account->username.'. Use /proxy:connect <ip> to connect to a Minecraft server."}');
	}
	else
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. Use /proxy:connect <ip> <username> to connect to a reverse proxy-compatible server."}');
	}
	$con->writeByte(1);
	$con->send();
};
$server->packet_function = function(ClientConnection $con, ServerboundPacketId $packetId)
{
	global $server_con, $server_eid, $transform_packets;
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
		if($packetId->name == "entity_action")
		{
			$server_con->startPacket("entity_action");
			$eid = $con->readVarInt();
			$server_con->writeVarInt(gmp_cmp($eid, $con->eid) == 0 ? $server_eid : $eid);
			$server_con->write_buffer .= $con->getRemainingData();
			$server_con->send();
		}
		else if($transform_packets && ($packet = $packetId->getInstance($con)))
		{
			$packet->send($server_con);
		}
		else if($packetId->since_protocol_version <= $server_con->protocol_version)
		{
			$server_con->write_buffer = $con->read_buffer;
			$server_con->send();
		}
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
$server->open_condition->add(function() use (&$client_con, &$server_con, &$server_eid, &$transform_packets)
{
	try
	{
		if($server_con instanceof ServerConnection && $client_con instanceof ClientConnection)
		{
			while(($packet_id = $server_con->readPacket(0)) !== false)
			{
				$packetId = ClientboundPacketId::getById($packet_id, $server_con->protocol_version);
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
					$packet = $packetId->getInstance($server_con);
					assert($packet instanceof EntityPacket);
					$packet->replaceEntity($server_eid, $client_con->eid);
					$packet->send($client_con);
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
				else if($transform_packets && ($packet = $packetId->getInstance($server_con)))
				{
					$packet->send($client_con);
				}
				else if($packetId->since_protocol_version <= $client_con->protocol_version)
				{
					$client_con->write_buffer = $server_con->read_buffer;
					$client_con->send();
				}
			}
		}
	}
	catch(Exception $e)
	{
		echo "Closing all connections: ".get_class($e)." ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
		$client_con->disconnect(get_class($e).": ".$e->getMessage());
		$client_con = null;
		$server_con->close();
		$server_con = null;
	}
}, 0.001);
$server->open_condition->add(function() use (&$client_con, &$server_con)
{
	PluginManager::fire(new ProxyTickEvent($client_con, $server_con));
}, 0.05);
pas::loop();
