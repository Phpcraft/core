<?php /** @noinspection PhpUndefinedFieldInspection */
/**
 * Provides clients with some essential first packets.
 *
 * @var Plugin $this
 */
use Phpcraft\
{BlockState, Chunk, ChunkSection, ClientConnection, Event\ServerChunkBorderEvent, Event\ServerJoinEvent, Event\ServerTickEvent, Plugin, PluginManager, Point3D};
global $grass_chunk, $stone_chunk, $grass_stone_chunk;
$grass_chunk = new Chunk();
$grass_chunk->setSection(0, new ChunkSection(BlockState::get("grass_block")));
$stone_chunk = new Chunk();
$stone_chunk->setSection(0, new ChunkSection(BlockState::get("stone")));
$grass_stone_chunk = new Chunk();
for($x = 0; $x < 16; $x++)
{
	for($y = 0; $y < 16; $y++)
	{
		for($z = 0; $z < 16; $z++)
		{
			$grass_stone_chunk->set(new Point3D($x, $y, $z), BlockState::get($x % 2 ? "grass_block" : "stone"));
		}
	}
}
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
	if(PluginManager::$command_prefix == "/" && $con->hasPermission("change the world"))
	{
		$con->sendMessage("Use /grass, /stone, and /grass_stone to §ochange the world§r.");
	}
	global $grass_stone_chunk;
	$con->chunk_preference = $grass_stone_chunk;
	$this->fire(new ServerChunkBorderEvent($event->server, $con));
})
	 ->registerCommand("grass", function(ClientConnection &$client)
	 {
		 global $grass_chunk;
		 $client->chunk_preference = $grass_chunk;
		 $client->chunks = [];
		 $this->fire(new ServerChunkBorderEvent($client->getServer(), $client));
	 }, "change the world")
	 ->registerCommand("stone", function(ClientConnection &$client)
	 {
	 	global $stone_chunk;
		 $client->chunk_preference = $stone_chunk;
		 $client->chunks = [];
		 $this->fire(new ServerChunkBorderEvent($client->getServer(), $client));
	 }, "change the world")
	 ->registerCommand("grass_stone", function(ClientConnection &$client)
	 {
		 global $grass_stone_chunk;
		 $client->chunk_preference = $grass_stone_chunk;
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
			 if(@$con->chunk_preference === null || $con->downstream !== null || @$con->received_imitated_world)
			 {
				 continue;
			 }
			 foreach($con->chunk_queue as $chunk_name => $chunk_coords)
			 {
				 $con->startPacket("chunk_data");
				 $con->writeInt($chunk_coords[0]); // Chunk X
				 $con->writeInt($chunk_coords[1]); // Chunk Z
				 $con->writeBoolean(true); // Is New Chunk
				 $con->chunk_preference->write($con);
				 file_put_contents("chunk.bin",  $con->write_buffer);
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
