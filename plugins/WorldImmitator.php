<?php
// WorldImmitator by timmyRS
// Provides clients connecting to the Phpcraft server with the packets captured by the WorldSaver client plugin.

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
if(!file_exists("world.bin"))
{
	echo "[WorldImmitator] Not loading because world.bin was not found.\n";
	return;
}
PluginManager::registerPlugin("WorldImmitator", function($plugin)
{
	global $WorldImmitator_version;
	$fh = fopen("world.bin", "r");
	$con = new \Phpcraft\Connection(-1, $fh);
	$WorldImmitator_version = $con->readPacket();
	fclose($fh);
	echo "[WorldImmitator] Loaded packets from ".\Phpcraft\Phpcraft::getMinecraftVersionsFromProtocolVersion($WorldImmitator_version)[0]." (protocol version ".$WorldImmitator_version.").\n";
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		global $WorldImmitator_version;
		if($event->data["client"]->protocol_version != $WorldImmitator_version)
		{
			$event->data["client"]->disconnect("[WorldImmitator] I have packets for ".\Phpcraft\Phpcraft::getMinecraftVersionRangeFromProtocolVersion($WorldImmitator_version)." (protocol version ".$WorldImmitator_version.") and we don't wanna find out what happens if I send them to you.");
			$event->cancel();
		}
	});
	$plugin->on("joined", function($event)
	{
		global $WorldImmitator_version;
		$fh = fopen("world.bin", "r");
		$con = new \Phpcraft\Connection($WorldImmitator_version, $fh);
		$con->readPacket();
		while($id = $con->readPacket())
		{
			$event->data["client"]->write_buffer = \Phpcraft\Phpcraft::intToVarInt($id).$con->read_buffer;
			$event->data["client"]->send();
			time_nanosleep(0, 10000000); // 10 ms
		}
		fclose($fh);
	});
});
