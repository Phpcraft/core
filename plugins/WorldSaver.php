<?php
// Stores world-related packets received by the client so that the server can reproduce them using the WorldImitator plugin.
use Phpcraft\
{Connection, Event, Plugin, PluginManager};

if(!in_array(PluginManager::$platform, ["phpcraft:client"]))
{
	return;
}
PluginManager::registerPlugin("WorldSaver", function(Plugin $plugin)
{
	$plugin->on("load", function(Event $event)
	{
		$fh = fopen("world.bin", "w");
		if($fh === false)
		{
			echo "[WorldSaver] Failed to open world.bin.\n";
			return;
		}
		global $WorldSaver_con;
		$WorldSaver_con = new Connection($event->data["server_protocol_version"], $fh);
		$WorldSaver_con->writeVarInt($event->data["server_protocol_version"]);
		$WorldSaver_con->send();
	});
	$plugin->on("packet", function(Event $event)
	{
		if($event->isCancelled())
		{
			return;
		}
		if(in_array($event->data["packet_name"], [
			"update_sign_entity",
			"spawn_object",
			"spawn_experience_orb",
			"spawn_global_entity",
			"spawn_mob",
			"spawn_painting",
			"spawn_player",
			"entity_animation",
			"statistics",
			"update_block_entity",
			"block_action",
			"block_change",
			"boss_bar",
			"chat_message",
			"multi_block_change",
			"set_slot",
			"set_cooldown",
			"entity_status",
			"explosion",
			"unload_chunk",
			"change_game_state",
			"chunk_data",
			"spawn_particle",
			"join_game",
			"map_data",
			"entity",
			"entity_relative_move",
			"entity_look_and_relative_move",
			"entity_look",
			"vehicle_move",
			"crafting_recipe_response",
			"clientbound_player_abilities",
			"combat_event",
			"player_info",
			"teleport",
			"unlock_recipies",
			"destroy_entities",
			"remove_entity_effect",
			"entity_head_look",
			"world_border",
			"camera",
			"held_item_change",
			"display_scoreboard",
			"entity_metadata",
			"attach_entity",
			"entity_velocity",
			"entity_equipment",
			"set_experience",
			"scoreboard_objective",
			"set_passengers",
			"teams",
			"update_score",
			"spawn_position",
			"update_time",
			"title",
			"stop_sound",
			"sound_effect",
			"player_list_header_and_footer",
			"collect_item",
			"entity_teleport",
			"entity_properties",
			"entity_effect",
			"declare_recipes",
			"tags"
		]))
		{
			global $WorldSaver_con;
			if($WorldSaver_con instanceof Connection)
			{
				$WorldSaver_con->startPacket($event->data["packet_name"]);
				$WorldSaver_con->write_buffer .= $event->data["connection"]->read_buffer;
				$WorldSaver_con->send();
			}
		}
	});
});
