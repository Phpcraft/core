<?php
// Allows clients to double-jump.
use Phpcraft\
{Gamemode, Plugin, PluginManager, ServerFlyingChangeEvent, ServerOnGroundChangeEvent};
PluginManager::registerPlugin("DoubleJump", function(Plugin $plugin)
{
	$plugin->on(function(ServerOnGroundChangeEvent $event)
	{
		if($event->client->on_ground && !$event->client->can_fly)
		{
			$event->client->can_fly = true;
			$event->client->sendAbilities();
		}
	});
	$plugin->on(function(ServerFlyingChangeEvent $event)
	{
		if($event->client->flying && ($event->client->gamemode == Gamemode::SURVIVAL || $event->client->gamemode == Gamemode::ADVENTURE))
		{
			$con = $event->client;
			$con->can_fly = false;
			$con->flying = false;
			$con->sendAbilities();
			$y_perc = 100 / 90 * (90 - abs($event->client->pitch)) / 100;
			$x = sin(pi() / 180 * $event->client->yaw) * $y_perc * -13;
			$y = (1 - $y_perc) * 9 + 1;
			$z = cos(pi() / 180 * $event->client->yaw) * $y_perc * 13;
			$con->startPacket("entity_velocity");
			$con->writeVarInt($con->eid);
			$con->writeShort($x * 1000);
			$con->writeShort($y * 1000);
			$con->writeShort($z * 1000);
			$con->send();
		}
	});
});
