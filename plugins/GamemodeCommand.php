<?php
// Gamemode Command by timmyRS

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("GamemodeCommand", function($plugin)
{
	$plugin->on("chat_message", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		if(substr($event->data["message"], 0, 10) == "/gamemode ")
		{
			$gamemode = floatval(substr($event->data["message"], 10));
			$con = $event->data["client"];
			$con->startPacket("change_game_state");
			$con->writeByte(3);
			$con->writeFloat($gamemode);
			$con->send();
			$event->cancel();
		}
	});
});
