<?php
// Provides clients with some essential first packets.

use Phpcraft\
{ClientboundPluginMessagePacket, ClientConnection, Difficulty, Dimension, Event, Gamemode, JoinGamePacket, Plugin,
	PluginManager, PluginMessagePacket, Position};

if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("FirstPackets", function(Plugin $plugin)
{
	$plugin->on("join", function(Event $event)
	{
		if($event->isCancelled())
		{
			return;
		}
		$con = $event->data["client"];
		if(!$con instanceof ClientConnection)
		{
			return;
		}
		$packet = new JoinGamePacket();
		$packet->eid = $con->eid;
		$packet->gamemode = Gamemode::CREATIVE;
		$packet->dimension = Dimension::OVERWORLD;
		$packet->difficulty = Difficulty::PEACEFUL;
		$packet->send($con);
		$packet = new ClientboundPluginMessagePacket();
		$packet->channel = PluginMessagePacket::CHANNEL_BRAND;
		$packet->data = "\x08Phpcraft";
		$packet->send($con);
		global $WorldImitator_version;
		if($WorldImitator_version !== null)
		{
			return;
		}
		$con->startPacket("spawn_position");
		$con->writePosition($con->pos = new Position(0, 16, 0));
		$con->send();
		$con->startPacket("update_time");
		$con->writeLong(0); // World Age
		$con->writeLong(-6000); // Time of Day
		$con->send();
		$con->startPacket("player_list_header_and_footer");
		$con->writeString('{"text":"Phpcraft Server"}');
		$con->writeString('{"text":"github.com/timmyrs/Phpcraft"}');
		$con->send();
		$con->startPacket("clientbound_chat_message");
		$con->writeString('{"text":"Welcome to this Phpcraft server."}');
		$con->writeByte(1);
		$con->send();
	}, \Phpcraft\Event::PRIORITY_NORMAL);
	$plugin->on("tick", function($event)
	{
		global $WorldImitator_version;
		if($WorldImitator_version !== null)
		{
			return;
		}
		$chunks_limit = 2; // chunks/tick limit
		for($render_distance = 4; $render_distance <= 8; $render_distance += 2)
		{
			foreach($event->data["server"]->clients as $con)
			{
				if(!$con instanceof ClientConnection && $con->state != 3)
				{
					continue;
				}
				for($x = round(($con->pos->x - ($render_distance * 16)) / 16); $x <= round(($con->pos->x + ($render_distance * 16)) / 16); $x++)
				{
					for($z = round(($con->pos->z - ($render_distance * 16)) / 16); $z <= round(($con->pos->z + ($render_distance * 16)) / 16); $z++)
					{
						if(in_array("$x:$z", $con->chunks))
						{
							continue;
						}
						$con->startPacket("chunk_data");
						$con->writeInt($x, true); // Chunk X
						$con->writeInt($z, true); // Chunk Z
						$con->writeBoolean(true); // Is New Chunk
						if($con->protocol_version >= 70) // Sections Bit Mask
						{
							$con->writeVarInt(0b00000001);
						}
						else if($con->protocol_version >= 60)
						{
							$con->writeInt(0b00000001);
						}
						else
						{
							$con->writeShort(0b00000001);
						}
						// Data Size + Data:
						$data = new \Phpcraft\Connection();
						for($i = 0; $i < 1; $i++)
						{
							$data->writeByte(8); // Bits per Block
							$data->writeVarInt(2); // Palette Size
							$data->writeVarInt(\Phpcraft\BlockMaterial::get("grass_block")->getId($con->protocol_version));
							$data->writeVarInt(\Phpcraft\BlockMaterial::get("stone")->getId($con->protocol_version));
							$data->writeVarInt(512); // (4096 / (64 / Bits per Block))
							$data->write_buffer .= str_repeat("\x00\x01", 2048); // Blocks
							$data->write_buffer .= str_repeat("\x00", 2048); // Block Light
							$data->write_buffer .= str_repeat("\xFF", 2048); // Sky Light
						}
						$data->write_buffer .= str_repeat($con->protocol_version >= 357 ? "\x00\x00\x00\x7F" : "\x00", 256); // Biomes
						$con->writeVarInt(strlen($data->write_buffer));
						$con->write_buffer .= $data->write_buffer;
						if($con->protocol_version >= 110)
						{
							$con->writeVarInt(0); // Number of block entities
						}
						$con->send();
						if(count($con->chunks) == 0)
						{
							$con->startPacket("teleport");
							$con->writePrecisePosition($con->pos);
							$con->writeFloat(0);
							$con->writeFloat(0);
							$con->writeByte(0);
							if($con->protocol_version > 47)
							{
								$con->writeVarInt(0); // Teleport ID
							}
							$con->send();
						}
						array_push($con->chunks, "$x:$z");
						if(--$chunks_limit == 0)
						{
							return;
						}
					}
				}
			}
		}
	});
});
