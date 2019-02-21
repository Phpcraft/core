<?php
// Provides clients with some essential first packets.

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("FirstPackets", function($plugin)
{
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		$con = $event->data["client"];
		$packet = new \Phpcraft\JoinGamePacket();
		$packet->entityId = 1337;
		$packet->gamemode = \Phpcraft\Gamemode::CREATIVE;
		$packet->dimension = \Phpcraft\Dimension::OVERWORLD;
		$packet->difficulty = \Phpcraft\Difficulty::PEACEFUL;
		$packet->send($con);
		$con->startPacket("plugin_message");
		$con->writeString($con->protocol_version > 340 ? "minecraft:brand" : "MC|Brand");
		$con->writeString("\\Phpcraft\\Server");
		$con->send();
		$con->startPacket("spawn_position");
		$con->writePosition(0, 100, 0);
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
		$con->startPacket("time_update");
		$con->writeLong(0); // World Age
		$con->writeLong(-6000); // Time of Day
		$con->send();
		$con->startPacket("player_list_header_and_footer");
		$con->writeString('{"text":"Phpcraft Server"}');
		$con->writeString('{"text":"github.com/timmyrs/Phpcraft"}');
		$con->send();
		$con->startPacket("chat_message");
		$con->writeString('{"text":"Welcome to this Phpcraft server."}');
		$con->writeByte(1);
		$con->send();
		$con->startPacket("chat_message");
		$con->writeString('{"text":"You can chat with other players here. That\'s it."}');
		$con->writeByte(1);
		$con->send();
	}, \Phpcraft\Event::PRIORITY_LOWEST);
});
