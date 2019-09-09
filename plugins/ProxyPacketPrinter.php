<?php
/**
 * The plugin with the catchiest and most concise name!
 * Prints packets that pass through the proxy. Surprise!
 *
 * @var Plugin $this
 */
use Phpcraft\
{Connection, Event\ProxyClientPacketEvent, Event\ProxyServerPacketEvent, Plugin};
$this->on(function(ProxyClientPacketEvent &$e)
{
	$packet_class = $e->packetId->getClass();
	if($packet_class)
	{
		$con = new Connection($e->client->protocol_version);
		$con->read_buffer = $e->server->read_buffer;
		$packet = call_user_func($packet_class."::read", $con);
		echo "S -> C: $packet\n";
	}
	else
	{
		echo "S -> C: {$e->packetId->name}\n";
	}
})->on(function(ProxyServerPacketEvent &$e)
{
	$recipient = ($e->server ? "S" : "P");
	$packet_class = $e->packetId->getClass();
	if($packet_class)
	{
		$con = new Connection($e->client->protocol_version);
		$con->read_buffer = $e->client->read_buffer;
		$packet = call_user_func($packet_class."::read", $con);
		echo "C -> $recipient: $packet\n";
	}
	else
	{
		echo "C -> $recipient: {$e->packetId->name}\n";
	}
});
