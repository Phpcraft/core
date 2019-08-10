<?php
/**
 * Provides clients with some essential first packets.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Connection, Enum\Difficulty, Enum\Dimension, Enum\Gamemode, Event\Event, Event\ServerChatEvent, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerTickEvent, Material, Packet\ClientboundBrandPluginMessagePacket, Packet\JoinGamePacket, Plugin, Position};
$WorldImitatorActive = false;
$client_chunk_preferences = [];
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	$con = $event->client;
	(new ClientboundBrandPluginMessagePacket("Phpcraft"))->send($con);
	global $WorldImitatorActive;
	if($WorldImitatorActive)
	{
		return;
	}
	$packet = new JoinGamePacket();
	$packet->eid = $con->eid;
	$packet->gamemode = $con->gamemode = Gamemode::CREATIVE;
	$packet->dimension = Dimension::OVERWORLD;
	$packet->difficulty = Difficulty::PEACEFUL;
	$packet->send($con);
	$con->setAbilities($con->gamemode);
	$con->sendAbilities();
	$con->startPacket("spawn_position");
	$con->writePosition($con->pos = new Position(0.0, 16.0, 0.0));
	$con->send();
	$con->startPacket("update_time");
	$con->writeLong(0); // World Age
	$con->writeLong(-6000); // Time of Day
	$con->send();
	$con->startPacket("player_list_header_and_footer");
	$con->writeString('{"text":"Phpcraft Server"}');
	$con->writeString('{"text":"github.com/timmyrs/Phpcraft"}');
	$con->send();
	$con->sendMessage("Welcome to this Phpcraft server.");
	$con->sendMessage("Use /grass, /stone, and /grass_stone to §ochange the world§r.");
	global $client_chunk_preferences;
	$client_chunk_preferences[$con->username] = "\x00\x01";
}, Event::PRIORITY_NORMAL);
$this->on(function(ServerChatEvent $event)
{
	if($event->message == "/grass")
	{
		global $client_chunk_preferences;
		$client_chunk_preferences[$event->client->username] = "\x00\x00";
		$event->client->chunks = [];
		$event->cancelled = true;
	}
	else if($event->message == "/stone")
	{
		global $client_chunk_preferences;
		$client_chunk_preferences[$event->client->username] = "\x01\x01";
		$event->client->chunks = [];
		$event->cancelled = true;
	}
	else if($event->message == "/grass_stone")
	{
		global $client_chunk_preferences;
		$client_chunk_preferences[$event->client->username] = "\x00\x01";
		$event->client->chunks = [];
		$event->cancelled = true;
	}
}, Event::PRIORITY_HIGH);
$this->on(function(ServerLeaveEvent $event)
{
	global $client_chunk_preferences;
	unset($client_chunk_preferences[$event->client->username]);
}, Event::PRIORITY_HIGH);
$this->on(function(ServerTickEvent $event)
{
	global $WorldImitatorActive;
	if($WorldImitatorActive)
	{
		return;
	}
	$chunks_limit = 2; // chunks/tick limit
	for($render_distance = 4; $render_distance <= 8; $render_distance += 2)
	{
		global $client_chunk_preferences;
		foreach($event->server->clients as $con)
		{
			assert($con instanceof ClientConnection);
			if($con->state != 3)
			{
				continue;
			}
			$chunk_preference = $client_chunk_preferences[$con->username];
			for($x = round(($con->pos->x - ($render_distance * 16)) / 16); $x <= round(($con->pos->x + ($render_distance * 16)) / 16); $x++)
			{
				for($z = round(($con->pos->z - ($render_distance * 16)) / 16); $z <= round(($con->pos->z + ($render_distance * 16)) / 16); $z++)
				{
					if(in_array("$x:$z", $con->chunks))
					{
						continue;
					}
					// TODO: 1.14 Support
					if($con->protocol_version < 472)
					{
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
						$data = new Connection();
						for($i = 0; $i < 1; $i++)
						{
							$data->writeByte(8); // Bits per Block
							$data->writeVarInt(2); // Palette Size
							$data->writeVarInt(Material::get("grass_block")
													   ->getId($con->protocol_version));
							$data->writeVarInt(Material::get("stone")
													   ->getId($con->protocol_version));
							$data->writeVarInt(512); // (4096 / (64 / Bits per Block))
							$data->write_buffer .= str_repeat($chunk_preference, 2048); // Blocks
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
					}
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
