<?php
/**
 * The plugin with the catchiest and most concise name!
 * Prints packets that pass through the proxy. Surprise!
 *
 * @var Plugin $this
 */
use Phpcraft\
{Event\ProxyClientPacketEvent, Event\ProxyServerPacketEvent, Plugin};
$this->on(function(ProxyClientPacketEvent &$e)
{
	$packet_class = $e->packetId->getClass();
	if($packet_class)
	{
		$offset = $e->server->read_buffer_offset;
		$packet = call_user_func($packet_class."::read", $e->server);
		echo "S -> C: $packet\n";
		$e->server->read_buffer_offset = $offset;
	}
	else
	{
		echo "S -> C: {$e->packetId->name}\n";
	}
})
	 ->on(function(ProxyServerPacketEvent &$e)
	 {
		 $recipient = ($e->server ? "S" : "P");
		 $packet_class = $e->packetId->getClass();
		 if($packet_class)
		 {
			 $offset = $e->client->read_buffer_offset;
			 $packet = call_user_func($packet_class."::read", $e->client);
			 echo "C -> $recipient: $packet\n";
			 $e->client->read_buffer_offset = $offset;
		 }
		 else
		 {
			 echo "C -> $recipient: {$e->packetId->name}\n";
		 }
	 });
