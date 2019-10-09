<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Packet\BossBar\BossBarPacket, Packet\DeclareCommands\DeclareCommandsPacket, Packet\MapData\MapDataPacket, Packet\PluginMessage\ClientboundPluginMessagePacket, PacketId};
/** The class for the IDs of packets sent to the client. */
class ClientboundPacket extends PacketId
{
	protected static $all_cache;

	static protected function populateAllCache()
	{
		self::populateAllCache_("toClient", self::nameMap(), function(string $name, int $pv)
		{
			return new ClientboundPacket($name, $pv);
		});
	}

	private static function nameMap(): array
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
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toClient", self::nameMap()) : null;
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
				return DestroyEntitiesPacket::class;
			case "clientbound_abilities":
				return ClientboundAbilitiesPacket::class;
			case "join_game":
				return JoinGamePacket::class;
			case "keep_alive_request":
				return KeepAliveRequestPacket::class;
			case "map_data":
				return MapDataPacket::class;
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
