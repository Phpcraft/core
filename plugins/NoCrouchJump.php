<?php
/**
 * Disallows clients to jump whilst crouching.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Connection, EffectType, Event\ServerClientMetadataEvent, Packet\EntityEffectPacket, Packet\RemoveEntityEffectPacket, Plugin};
$this->on(function(ServerClientMetadataEvent $event)
{
	if($event->client->entityMetadata->crouching)
	{
		(new EntityEffectPacket($event->client->eid, EffectType::get("jump_boost"), -128, Connection::$pow2[32], false))->send($event->client);
	}
	else if($event->prev_metadata->crouching && !$event->client->entityMetadata->crouching)
	{
		(new RemoveEntityEffectPacket($event->client->eid, EffectType::get("jump_boost")))->send($event->client);
	}
});
