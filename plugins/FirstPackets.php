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
		if($con->protocol_version > 340) // 1.13+
		{
			for($x = -16; $x <= 16; $x++)
			{
				for($y = -16; $y <= 16; $y++)
				{
					$con->startPacket("chunk_data");
					$con->writeInt($x, true); // Chunk X
					$con->writeInt($y, true); // Chunk Y
					$con->writeBoolean(true); // Is New Chunk
					$con->writeVarInt(0b00000001); // Sections Bit Mask
					// Data Size + Data:
					$data = new \Phpcraft\Connection();
					$data->writeByte(8); // Bits per Block
					$data->writeVarInt(1); // Palette Size
					$data->writeVarInt(\Phpcraft\BlockMaterial::get("grass_block")->id);
					$data->write_buffer .= str_repeat("\x00", 4096); // Blocks
					$data->write_buffer .= str_repeat("\x00", 2048); // Block Light
					$data->write_buffer .= str_repeat("\xFF", 2048); // Sky Light
					$data->write_buffer .= str_repeat("\x00\x00\x00\x7F", 256); // Biomes
					$con->writeVarInt(strlen($data->write_buffer));
					$con->write_buffer .= $data->write_buffer;
					$con->writeVarInt(0); // Number of block entities
					$con->send();
				}
			}
		}
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
	}, \Phpcraft\Event::PRIORITY_NORMAL);
});
