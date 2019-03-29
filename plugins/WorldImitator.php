<?php
// Provides clients connecting to the server with the packets captured by the WorldSaver plugin.
use Phpcraft\
{ServerJoinEvent, Connection, Event, Phpcraft, Plugin, PluginManager, Versions};

if(!file_exists("world.bin"))
{
	echo "[WorldImitator] Not loading because world.bin was not found.\n";
	return;
}
global $WorldImitator_version;
$fh = fopen("world.bin", "r");
if($fh === false)
{
	echo "[WorldImitator] Failed to open world.bin.\n";
	return;
}
$con = new Connection(-1, $fh);
$WorldImitator_version = $con->readPacket();
fclose($fh);
echo "[WorldImitator] Loaded packets from ".Versions::protocolToRange($WorldImitator_version)." (protocol version ".strval($WorldImitator_version).").\n";
PluginManager::registerPlugin("WorldImitator", function(Plugin $plugin)
{
	$plugin->on(function(ServerJoinEvent $event)
	{
		if($event->cancelled)
		{
			return;
		}
		global $WorldImitator_version;
		if($event->client->protocol_version != $WorldImitator_version)
		{
			$event->client->disconnect("Please join using ".Versions::protocolToRange($WorldImitator_version)." (protocol version ".$WorldImitator_version.")");
			$event->cancelled = true;
			return;
		}
		$fh = fopen("world.bin", "r");
		$con = new Connection($WorldImitator_version, $fh);
		$con->readPacket();
		while($id = $con->readPacket(0))
		{
			$event->client->write_buffer = Phpcraft::intToVarInt($id).$con->read_buffer;
			$event->client->send();
		}
		fclose($fh);
	}, Event::PRIORITY_LOWEST);
});
