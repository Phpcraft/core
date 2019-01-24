<?php
// WorldSaver by timmyRS
// Stores world-related packets received by the client so that the Phpcraft server can reproduce them using the WorldImmitator plugin.

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:client"]))
{
	return;
}
PluginManager::registerPlugin("WorldSaver", function($plugin)
{
	$plugin->on("load", function($event)
	{
		global $WorldSaver_con;
		$WorldSaver_con = new \Phpcraft\Connection($event->data["server_protocol_version"], fopen("world.bin", "w"));
		$WorldSaver_con->writeVarInt($event->data["server_protocol_version"]);
		$WorldSaver_con->send();
	});
	$plugin->on("packet", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		if(in_array($event->data["packet_name"], [
			"spawn_mob",
			"spawn_player",
			"block_change",
			"boss_bar",
			"chat_message",
			"multi_block_change",
			"plugin_message",
			"chunk_data",
			"entity_relative_move",
			"entity_look_and_relative_move",
			"entity_look",
			"player_list_item",
			"teleport",
			"destroy_entites",
			"display_scoreboard",
			"scoreboard_objective",
			"update_score",
			"spawn_position",
			"time_update",
			"player_list_header_and_footer",
			"entity_teleport"
		]))
		{
			global $WorldSaver_con;
			$WorldSaver_con->startPacket($event->data["packet_name"]);
			$WorldSaver_con->write_buffer .= $event->data["connection"]->read_buffer;
			$WorldSaver_con->send();
		}
	});
});
