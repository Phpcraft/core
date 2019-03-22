<?php
namespace Phpcraft;
/**
 * The class for the IDs of packets sent to the client.
 */
class ClientboundPacket extends PacketId
{
	private static $all_cache;

	private static function nameMap()
	{
		return [
			"spawn_entity" => "spawn_object",
			"spawn_entity_experience_orb" => "sapwn_experience_orb",
			"spawn_entity_weather" => "spawn_global_entity",
			"spawn_entity_living" => "spawn_mob",
			"spawn_entity_painting" => "spawn_painting",
			"named_entity_spawn" => "spawn_player",
			"login" => "join_game",
			"map_chunk" => "chunk_data",
			"position" => "teleport",
			"playerlist_header" => "player_list_header_and_footer",
			"map" => "map_data",
			"game_state_change" => "change_game_state",
			"experience" => "set_experience",

			"keep_alive" => "keep_alive_request",
			"abilities" => "clientbound_abilities",
			"chat" => "clientbound_chat_message",
			"custom_payload" => "clientbound_plugin_message"
		];
	}

	/**
	 * @copydoc Identifier::all
	 */
	public static function all()
	{
		if(self::$all_cache == null)
		{
			self::$all_cache = self::_all("toClient", self::nameMap(), function($name, $pv)
			{
				return new ClientboundPacket($name, $pv);
			});
		}
		return self::$all_cache;
	}

	/**
	 * @copydoc Identifier::getId
	 */
	public function getId($protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toClient", self::nameMap()) : null;
	}

	/**
	 * @copydoc PacketId::init
	 */
	public function init(Connection $con)
	{
		switch($this->name)
		{
			case "boss_bar":
			return BossBarPacket::read($con);

			case "join_game":
			return JoinGamePacket::read($con);

			case "keep_alive_request":
			return KeepAliveRequestPacket::read($con);

			case "map_data":
			return MapDataPacket::read($con);

			case "set_experience":
			return SetExperiencePacket::read($con);

			case "set_slot":
			return SetSlotPacket::read($con);

			case "spawn_mob":
			return SpawnMobPacket::read($con);

			case "clientbound_plugin_message":
			return ClientboundPluginMessagePacket::read($con);
		}
		return null;
	}
}
