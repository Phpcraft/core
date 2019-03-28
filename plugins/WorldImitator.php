<?php
// Provides clients connecting to the server with the packets captured by the WorldSaver plugin.
use Phpcraft\
{ClientConnection, Connection, Event, Phpcraft, Plugin, PluginManager, Versions};

if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
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
	$plugin->on("join", function(Event $event)
	{
		if($event->isCancelled())
		{
			return;
		}
		global $WorldImitator_version;
		$client_con = $event->data["client"];
		if(!$client_con instanceof ClientConnection)
		{
			return;
		}
		if($client_con->protocol_version != $WorldImitator_version)
		{
			$client_con->disconnect("Please join using ".Versions::protocolToRange($WorldImitator_version)." (protocol version ".$WorldImitator_version.")");
		}
		else
		{
			$fh = fopen("world.bin", "r");
			$con = new \Phpcraft\Connection($WorldImitator_version, $fh);
			$con->readPacket();
			while($id = $con->readPacket(0))
			{
				$client_con->write_buffer = Phpcraft::intToVarInt($id).$con->read_buffer;
				$client_con->send();
			}
			fclose($fh);
		}
	}, \Phpcraft\Event::PRIORITY_LOWEST);
});
