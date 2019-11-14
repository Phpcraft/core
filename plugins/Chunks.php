<?php /** @noinspection PhpUndefinedFieldInspection */
/**
 * Provides clients with some essential first packets.
 *
 * @var Plugin $this
 */
use Phpcraft\
{BlockState, ClientConnection, Connection, Event\ServerChunkBorderEvent, Event\ServerJoinEvent, Event\ServerTickEvent, NBT\CompoundTag, NBT\LongArrayTag, Plugin, PluginManager};
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	$con = $event->client;
	if(@$con->received_imitated_world)
	{
		return;
	}
	$con->startPacket("update_time");
	$con->writeLong(0); // World Age
	$con->writeLong(-6000); // Time of Day
	$con->send();
	if($con->hasPermission("change the world"))
	{
		$con->sendMessage("Use /grass, /stone, and /grass_stone to §ochange the world§r.");
	}
	$con->chunk_preference = "\x00\x01";
	$this->fire(new ServerChunkBorderEvent($event->server, $con));
})
	 ->registerCommand("grass", function(ClientConnection &$client)
	 {
		 $client->chunk_preference = "\x00\x00";
		 $client->chunks = [];
		 $this->fire(new ServerChunkBorderEvent($client->getServer(), $client));
	 }, "change the world")
	 ->registerCommand("stone", function(ClientConnection &$client)
	 {
		 $client->chunk_preference = "\x01\x01";
		 $client->chunks = [];
		 $this->fire(new ServerChunkBorderEvent($client->getServer(), $client));
	 }, "change the world")
	 ->registerCommand("grass_stone", function(ClientConnection &$client)
	 {
		 $client->chunk_preference = "\x00\x01";
		 $client->chunks = [];
		 $this->fire(new ServerChunkBorderEvent($client->getServer(), $client));
	 }, "change the world")
	 ->on(function(ServerChunkBorderEvent $event)
	 {
		 $event->client->chunk_queue = [];
		 for($x = $event->client->chunk_x - $event->client->render_distance; $x <= $event->client->chunk_x + $event->client->render_distance; $x++)
		 {
			 for($z = $event->client->chunk_z - $event->client->render_distance; $z <= $event->client->chunk_z + $event->client->render_distance; $z++)
			 {
				 $name = "$x:$z";
				 if(!array_key_exists($name, $event->client->chunks))
				 {
					 $event->client->chunk_queue[$name] = [
						 $x,
						 $z
					 ];
				 }
			 }
		 }
	 })
	 ->on(function(ServerTickEvent $event)
	 {
		 if($event->lagging)
		 {
			 return;
		 }
		 $chunks_limit = 20; // chunks/tick limit
		 foreach($event->server->clients as $con)
		 {
			 assert($con instanceof ClientConnection);
			 if($con->state != Connection::STATE_PLAY || @$con->received_imitated_world)
			 {
				 continue;
			 }
			 foreach($con->chunk_queue as $chunk_name => $chunk_coords)
			 {
				 $con->startPacket("chunk_data");
				 $con->writeInt($chunk_coords[0]); // Chunk X
				 $con->writeInt($chunk_coords[1]); // Chunk Z
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
					 $motion_blocking = new LongArrayTag("MOTION_BLOCKING");
					 for($i = 0; $i < 36; $i++)
					 {
						 array_push($motion_blocking->children, gmp_init(substr($bits, $i * 64, 64), 2));
					 }
					 (new CompoundTag("", [
						 $motion_blocking,
						 //new NbtLongArray("WORLD_SURFACE", $motion_blocking->children)
					 ]))->write($con);
				 }
				 // Data Size + Data:
				 $data = new Connection();
				 //for($i = 0; $i < 1; $i++)
				 {
					 if($con->protocol_version > 47)
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
						 $data->write_buffer .= str_repeat($con->chunk_preference, 2048); // Blocks
					 }
					 else
					 {
						 $legacy_ids = "";
						 for($i = 0; $i < 2; $i++)
						 {
							 $legacy_ids .= substr($con->chunk_preference, $i, 1) == "\x00" ? "\x20\x00" : "\x10\x00"; // For some reason the byte order is reversed
						 }
						 $data->write_buffer .= str_repeat($legacy_ids, 2048);
						 $data->writeVarInt(16); // Bits of data per block: 4 for block light, 8 for block + sky light, 16 for both + biome.
						 $data->writeVarInt(8192); // Number of elements in block + sky light arrays
					 }
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
				 $con->chunks[$chunk_name] = true;
				 unset($con->chunk_queue[$chunk_name]);
				 if(--$chunks_limit == 0)
				 {
					 return;
				 }
			 }
		 }
	 });
