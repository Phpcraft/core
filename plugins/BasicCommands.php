<?php
// This plugin provides clients of the server with /abilities, /gamemode, and /metadata.
use Phpcraft\
{ClientConnection, Event\ServerChatEvent, Plugin, PluginManager};
PluginManager::registerPlugin("BasicCommands", function(Plugin $plugin)
{
	$plugin->on(function(ServerChatEvent $event)
	{
		if($event->cancelled || substr($event->message, 0, 1) != "/")
		{
			return;
		}
		$con = $event->client;
		if(!$con instanceof ClientConnection)
		{
			return;
		}
		if(substr($event->message, 0, 11) == "/abilities ")
		{
			$con->startPacket("clientbound_abilities");
			$con->writeByte(hexdec(substr($event->message, 11, 1)));
			$con->writeFloat(0.05);
			$con->writeFloat(0.1);
			$con->send();
		}
		else if(substr($event->message, 0, 10) == "/gamemode ")
		{
			$gamemode = intval(substr($event->message, 10));
			$con->setGamemode($gamemode);
			$con->startPacket("player_info");
			$con->writeVarInt(1);
			$con->writeVarInt(1);
			$con->writeUUID($con->uuid);
			$con->writeVarInt($gamemode);
			$con->send();
		}
		else if(substr($event->message, 0, 10) == "/metadata ")
		{
			$con->startPacket("entity_metadata");
			$con->writeVarInt($con->eid);
			$con->writeByte(0);
			$con->writeVarInt(0);
			$con->writeByte(hexdec(substr($event->message, 10, 2)));
			$con->writeByte(0xFF);
			$con->send();
		}
		else
		{
			$con->sendMessage("That's not any command I know. Try /abilities <0-F>, /gamemode <0-3>, or /metadata <00-FF>.");
		}
		$event->cancelled = true;
	});
});
