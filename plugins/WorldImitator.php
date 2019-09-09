<?php
/**
 * Provides clients connecting to the server with the packets captured by the WorldSaver plugin.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Connection, Event\Event, Event\ServerJoinEvent, Plugin, Versions};
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled || !file_exists("world.bin"))
	{
		return;
	}
	global $WorldImitatorActive;
	$WorldImitatorActive = true;
	$fh = fopen("world.bin", "r");
	$con = new Connection(-1, $fh);
	$version = $con->readPacket();
	if($event->client->protocol_version != $version)
	{
		$event->client->disconnect("Please join using ".Versions::protocolToRange($version)." (protocol version ".$version.")");
		$event->cancelled = true;
		return;
	}
	while(($id = $con->readPacket(0)) !== false)
	{
		$event->client->write_buffer = Connection::varInt($id).$con->read_buffer;
		$event->client->send();
	}
	fclose($fh);
}, Event::PRIORITY_HIGH);
