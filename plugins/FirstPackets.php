<?php
/**
 * Provides clients with some essential first packets.
 *
 * @var Plugin $this
 */
use Phpcraft\
{BlockState, ClientConnection, Command\CommandSender, Connection, Enum\Gamemode, Event\Event, Event\ServerClientSettingsEvent, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerTickEvent, Nbt\NbtCompound, Nbt\NbtLongArray, Packet\JoinGamePacket, Packet\PluginMessage\ClientboundBrandPluginMessagePacket, Plugin, PluginManager, Position};
if(PluginManager::$command_prefix == "/proxy:")
{
	$this->unregister();
	return;
}
$WorldImitatorActive = false;
$client_chunk_preferences = [];
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	$con = $event->client;
	global $WorldImitatorActive;
	if($WorldImitatorActive)
	{
		return;
	}
	$packet = new JoinGamePacket();
	$packet->eid = $con->eid;
	$packet->gamemode = $con->gamemode = Gamemode::CREATIVE;
	$packet->render_distance = 32;
	$packet->send($con);
	(new ClientboundBrandPluginMessagePacket("Phpcraft"))->send($con);
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
	if($con->hasPermission("change the world"))
	{
		$con->sendMessage("Use /grass, /stone, and /grass_stone to §ochange the world§r.");
	}
	global $client_chunk_preferences;
	$client_chunk_preferences[$con->username] = "\x00\x01";
}, Event::PRIORITY_NORMAL)
	 ->registerCommand("grass", function(CommandSender &$client)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 global $client_chunk_preferences;
		 $client_chunk_preferences[$client->username] = "\x00\x00";
		 $client->chunks = [];
	 }, "change the world")
	 ->registerCommand("stone", function(CommandSender &$client)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 global $client_chunk_preferences;
		 $client_chunk_preferences[$client->username] = "\x01\x01";
		 $client->chunks = [];
	 }, "change the world")
	 ->registerCommand("grass_stone", function(CommandSender &$client)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 global $client_chunk_preferences;
		 $client_chunk_preferences[$client->username] = "\x00\x01";
		 $client->chunks = [];
	 }, "change the world")
	 ->on(function(ServerLeaveEvent $event)
	 {
		 global $client_chunk_preferences;
		 unset($client_chunk_preferences[$event->client->username]);
	 }, Event::PRIORITY_HIGH)
	 ->on(function(ServerTickEvent $event)
	 {
		 if($event->lagging)
		 {
			 return;
		 }
		 global $WorldImitatorActive;
		 if($WorldImitatorActive)
		 {
			 return;
		 }
		 $chunks_limit = 20; // chunks/tick limit
		 global $client_chunk_preferences;
		 foreach($event->server->clients as $con)
		 {
			 assert($con instanceof ClientConnection);
			 if($con->state != 3)
			 {
				 continue;
			 }
			 $chunk_preference = $client_chunk_preferences[$con->username];
			 for($x = $con->chunk_x - $con->render_distance; $x <= $con->chunk_x + $con->render_distance; $x++)
			 {
				 for($z = $con->chunk_z - $con->render_distance; $z <= $con->chunk_z + $con->render_distance; $z++)
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
					 if($con->protocol_version >= 472) // Height map
					 {
						 $bits = str_repeat("000001111", 256);
						 $motion_blocking = new NbtLongArray("MOTION_BLOCKING");
						 for($i = 0; $i < 36; $i++)
						 {
							 array_push($motion_blocking->children, gmp_init(substr($bits, $i * 64, 64), 2));
						 }
						 (new NbtCompound("", [
							 $motion_blocking,
							 //new NbtLongArray("WORLD_SURFACE", $motion_blocking->children)
						 ]))->write($con);
					 }
					 // Data Size + Data:
					 $data = new Connection();
					 //for($i = 0; $i < 1; $i++)
					 {
						 if($con->protocol_version >= 472)
						 {
							 $data->writeShort(4096); // Block count
						 }
						 $data->writeByte(8); // Bits per Block
						 $data->writeVarInt(2); // Palette Size
						 $data->writeVarInt(BlockState::get("grass_block")
													  ->getId($con->protocol_version));
						 $data->writeVarInt(BlockState::get("stone")
													  ->getId($con->protocol_version));
						 $data->writeVarInt(512); // (4096 / (64 / Bits per Block))
						 $data->write_buffer .= str_repeat($chunk_preference, 2048); // Blocks
						 if($con->protocol_version < 472)
						 {
							 $data->write_buffer .= str_repeat("\x00", 2048); // Block Light
							 $data->write_buffer .= str_repeat("\xFF", 2048); // Sky Light
						 }
					 }
					 $data->write_buffer .= str_repeat($con->protocol_version >= 357 ? "\x00\x00\x00\x7F" : "\x00", 256); // Biomes
					 $con->writeVarInt(strlen($data->write_buffer));
					 $con->write_buffer .= $data->write_buffer;
					 if($con->protocol_version >= 110)
					 {
						 $con->writeVarInt(0); // Number of block entities
					 }
					 $con->send();
					 /*if($con->protocol_version >= 472)
					 {
						 $con->startPacket("update_light");
						 $con->writeVarInt($x);
						 $con->writeVarInt($z);
						 $con->writeVarInt(0b111111111111111100);
						 $con->writeVarInt(0);
						 $con->writeVarInt(0b11);
						 $con->writeVarInt(0b111111111111111111);
						 for($i = 0; $i < 16; $i++)
						 {
							 $con->writeVarInt(2048);
							 $con->write_buffer .= str_repeat("\xFF", 2048);
						 }
						 $con->send();
					 }*/
					 if(count($con->chunks) == 0)
					 {
						 $con->teleport($con->pos);
					 }
					 array_push($con->chunks, "$x:$z");
					 if(--$chunks_limit == 0)
					 {
						 return;
					 }
				 }
			 }
		 }
	 });
