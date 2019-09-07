<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException, Packet\BossBar\BossBarPacket, Packet\PluginMessage\ClientboundPluginMessagePacket, PacketId};
/**
 * The class for the IDs of packets sent to the client.
 */
class ClientboundPacket extends PacketId
{
	private static $all_cache;

	/**
	 * Returns every ClientboundPacket.
	 *
	 * @return ClientboundPacket[]
	 */
	static function all(): array
	{
		if(self::$all_cache == null)
		{
			self::$all_cache = self::_all("toClient", self::nameMap(), function(string $name, int $pv)
			{
				return new ClientboundPacket($name, $pv);
			});
		}
		return self::$all_cache;
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
	 * @param integer $protocol_version
	 * @return integer|null
	 */
	function getId(int $protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toClient", self::nameMap()) : null;
	}

	/**
	 * Initialises this packet's class by reading its payload from the given Connection.
	 * Returns null if the packet does not have a class implementation yet.
	 *
	 * @param Connection $con
	 * @return Packet|null
	 * @throws IOException
	 */
	function init(Connection $con)
	{
		switch($this->name)
		{
			case "clientbound_abilities":
				return ClientboundAbilitiesPacket::read($con);
			case "boss_bar":
				return BossBarPacket::read($con);
			case "destroy_entities":
				return DestroyEntitiesPacket::read($con);
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
