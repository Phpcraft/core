<?php /** @noinspection PhpUndefinedFieldInspection */
/**
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Entity\EntityType, Event\ServerJoinEvent, Event\ServerTickEvent, Packet\SpawnMobPacket, Plugin, PluginManager};
if(PluginManager::$command_prefix != "/")
{
	$this->unregister();
	return;
}
$this->on(function(ServerJoinEvent $event)
{
	if(@$event->client->received_imitated_world)
	{
		return;
	}
	if(EntityType::get("shulker")->since_protocol_version <= $event->client->protocol_version)
	{
		$event->client->shulker_eid = $event->server->eidCounter->next();
		$event->client->guardian_eid = $event->server->eidCounter->next();
		$event->client->shulker_pos = $event->client->pos->add(0.5, -1, 0.5);
	}
})
	 ->on(function(ServerTickEvent $event)
	 {
		 if($event->lagging)
		 {
			 return;
		 }
		 foreach($event->server->clients as $con)
		 {
			 assert($con instanceof ClientConnection);
			 if(@$con->lock_on)
			 {
				 list($yaw, $pitch) = $con->getEyePosition()
										  ->lookAt($con->lock_on);
				 $con->rotate($yaw, $pitch);
			 }
			 else if(@$con->shulker_eid !== null)
			 {
				 $pos = $con->getEyePosition();
				 $vec = $con->getUnitVector();
				 $i = 0;
				 do
				 {
					 $pos = $pos->add($vec);
				 }
				 while($pos->y > 16 && $i++ < 128);
				 $pos = $pos->block();
				 if(!$pos->equals($con->shulker_pos))
				 {
					 $con->shulker_pos = $pos;
					 $packet = new SpawnMobPacket($con->shulker_eid, EntityType::get("shulker"));
					 $packet->pos = $con->shulker_pos;
					 $packet->metadata->invisible = true;
					 $packet->metadata->glowing = true;
					 $packet->send($con);
				 }
			 }
			 if(@$con->guardian_eid !== null)
			 {
				 $packet = new SpawnMobPacket($con->guardian_eid, EntityType::get("guardian"));
				 $packet->pos = $con->getEyePosition()
									->add($con->getUnitVector()
											  ->multiply(-3));
				 $packet->metadata->invisible = true;
				 $packet->metadata->target_eid = $con->shulker_eid;
				 $packet->send($con);
			 }
		 }
	 });
$this->registerCommand("lock", function(ClientConnection $sender)
{
	$sender->lock_on = $sender->shulker_pos->blockCenter();
});
$this->registerCommand("unlock", function(ClientConnection $sender)
{
	$sender->lock_on = false;
});
$this->registerCommand("away", function(ClientConnection $con)
{
	$vec = $con->getUnitVector()
			   ->multiply(-5);
	$con->startPacket("entity_velocity");
	$con->writeVarInt($con->eid);
	$con->writeShort($vec->x * 2500);
	$con->writeShort(1000);
	$con->writeShort($vec->z * 2500);
	$con->send();
});
