<?php /** @noinspection PhpUndefinedFieldInspection */
/**
 * Provides clients connecting to the server with the packets captured by the WorldSaver plugin.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Connection, Event\Event, Event\ServerJoinEvent, Packet\ClientboundPacketId, Plugin, Versions};
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled || !file_exists("world.bin"))
	{
		return;
	}
	$fh = fopen("world.bin", "r");
	$con = new Connection(-1, $fh);
	$join_game_packet_id = ClientboundPacketId::get("join_game")
											  ->getId($event->client->protocol_version);
	$version = $con->readPacket();
	if($event->client->protocol_version != $version)
	{
		$event->client->disconnect("Please join using ".Versions::protocolToRange($version)." (protocol version ".$version.")");
		$event->cancelled = true;
		return;
	}
	while(($id = $con->readPacket(0)) !== false)
	{
		if($id == $join_game_packet_id)
		{
			$event->client->eid = gmp_intval($con->readInt());
		}
		$event->client->write_buffer = $con->read_buffer;
		$event->client->send();
	}
	fclose($fh);
	$event->client->received_imitated_world = true;
}, Event::PRIORITY_HIGH);
