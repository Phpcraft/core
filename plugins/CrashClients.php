<?php
// Crash Clients by timmyRS
// Crashes clients when they send "crash me" in the chat.

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("CrashClients", function($plugin)
{
	$plugin->on("chat_message", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		if($event->data["message"] == "crash me")
		{
			$con = $event->data["client"];
			echo $con->username." requested a crash.\n";
			$con->startPacket("change_game_state");
			$con->writeByte(7);
			$con->writeFloat(1337);
			$con->send();
			$event->cancel();
		}
	});
});
