<?php
// Provides clients connecting to the server with the packets captured by the WorldSaver plugin.

use \Phpcraft\PluginManager;
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
$con = new \Phpcraft\Connection(-1, $fh);
$WorldImitator_version = $con->readPacket();
fclose($fh);
echo "[WorldImitator] Loaded packets from ".\Phpcraft\Phpcraft::getMinecraftVersionsFromProtocolVersion($WorldImitator_version)[0]." (protocol version ".strval($WorldImitator_version).").\n";
PluginManager::registerPlugin("WorldImitator", function($plugin)
{
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		global $WorldImitator_version;
		if($event->data["client"]->protocol_version != $WorldImitator_version)
		{
			$event->data["client"]->startPacket("clientbound_chat_message");
			$event->data["client"]->writeString(json_encode(["text" => "[WorldImitator] I have packets for ".\Phpcraft\Phpcraft::getMinecraftVersionRangeFromProtocolVersion($WorldImitator_version)." (protocol version ".$WorldImitator_version.") and we don't wanna find out what happens if I send them to you."]));
			$event->data["client"]->writeByte(0);
			$event->data["client"]->send();
		}
		else
		{
			$fh = fopen("world.bin", "r");
			$con = new \Phpcraft\Connection($WorldImitator_version, $fh);
			$con->readPacket();
			while($id = $con->readPacket(0))
			{
				$event->data["client"]->write_buffer = \Phpcraft\Phpcraft::intToVarInt($id).$con->read_buffer;
				$event->data["client"]->send();
			}
			fclose($fh);
		}
	}, \Phpcraft\Event::PRIORITY_LOWEST);
});
