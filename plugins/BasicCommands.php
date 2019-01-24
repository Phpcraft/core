<?php
// Basic Commands
// Because the Phpcraft server doesn't have commands. :(

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("BasicCommands", function($plugin)
{
	$plugin->on("chat_message", function($event)
	{
		if($event->isCancelled() || substr($event->data["message"], 0, 1) != "/")
		{
			return;
		}
		$con = $event->data["client"];
		if(substr($event->data["message"], 0, 10) == "/gamemode ")
		{
			$gamemode = floatval(substr($event->data["message"], 10));
			$con->startPacket("change_game_state");
			$con->writeByte(3);
			$con->writeFloat($gamemode);
			$con->send();
		}
		else if(substr($event->data["message"], 0, 10) == "/metadata ")
		{
			$con->startPacket("entity_metadata");
			$con->writeVarInt(1337);
			$con->writeByte(0);
			$con->writeVarInt(0);
			$con->writeByte(hexdec(substr($event->data["message"], 10, 2)));
			$con->writeByte(0xFF);
			$con->send();
		}
		else
		{
			$con->startPacket("chat_message");
			$con->writeString(json_encode(["text" => "That's not any command I know. Try /gamemode <0-3> or /metadata <00-FF>. That's it."]));
			$con->writeByte(0);
			$con->send();
		}
		$event->cancel();
	});
});
