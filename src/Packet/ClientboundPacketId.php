<?php
namespace Phpcraft\Packet;
use Phpcraft\Packet\
{BossBar\BossBarPacket, DeclareCommands\DeclareCommandsPacket, MapData\MapDataPacket, PluginMessage\ClientboundPluginMessagePacket};
use Phpcraft\PacketId;
/** The class for the IDs of packets sent to the client. */
class ClientboundPacketId extends PacketId
{
	protected static $all_cache;

	/**
	 * Returns a ClientboundPacketId by its name or null if not found.
	 * If you call ClientboundPacketId::get() instead of PacketId::get() you may drop the leading "clientbound_" from applicable packet ids.
	 *
	 * @param string $name
	 * @return ClientboundPacketId|null
	 */
	static function get(string $name)
	{
		$name = strtolower($name);
		if(self::$all_cache === null)
		{
			self::populateAllCache();
		}
		return self::$all_cache[$name] ?? @self::$all_cache["clientbound_".$name];
	}

	static protected function populateAllCache()
	{
		self::populateAllCache_("toClient");
	}

	protected static function nameMap(): array
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
			"kick_disconnect" => "disconnect",
			"keep_alive" => "keep_alive_request",
			"animation" => "entity_animation",
			"abilities" => "clientbound_abilities",
			"chat" => "clientbound_chat_message",
			"custom_payload" => "clientbound_plugin_message"
		];
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	function getId(int $protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toClient") : null;
	}

	/**
	 * Returns the packet's class or null if the packet does not have a class implementation yet.
	 *
	 * @return string|null
	 */
	function getClass()
	{
		switch($this->name) // Ordered alphabetically
		{
			case "boss_bar":
				return BossBarPacket::class;
			case "declare_commands":
				return DeclareCommandsPacket::class;
			case "destroy_entities":
				return DestroyEntityPacket::class;
			case "clientbound_abilities":
				return ClientboundAbilitiesPacket::class;
			case "entity_animation":
				return EntityAnimationPacket::class;
			case "entity_effect":
				return EntityEffectPacket::class;
			case "entity_metadata":
				return EntityMetadataPacket::class;
			case "entity_velocity":
				return EntityVelocityPacket::class;
			case "join_game":
				return JoinGamePacket::class;
			case "keep_alive_request":
				return KeepAliveRequestPacket::class;
			case "map_data":
				return MapDataPacket::class;
			case "remove_entity_effect":
				return RemoveEntityEffect::class;
			case "set_experience":
				return SetExperiencePacket::class;
			case "set_slot":
				return SetSlotPacket::class;
			case "spawn_mob":
				return SpawnMobPacket::class;
			case "clientbound_plugin_message":
				return ClientboundPluginMessagePacket::class;
		}
		return null;
	}
}
