<?php
namespace Phpcraft;
/**
 * A Packet.
 * Look at the source code of this class for a list of packet names.
 */
abstract class Packet
{
	private static function clientboundPackets()
	{
		return [
			"update_sign_entity"            => [  -1,   -1,   -1,   -1,   -1,   -1, 0x33],
			"spawn_object"                  => [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x0E],
			"spawn_experience_orb"          => [0x01, 0x01, 0x01, 0x01, 0x01, 0x01, 0x11],
			"spawn_global_entity"           => [0x02, 0x02, 0x02, 0x02, 0x02, 0x02, 0x2C],
			"spawn_mob"                     => [0x03, 0x03, 0x03, 0x03, 0x03, 0x03, 0x0F],
			"spawn_painting"                => [0x04, 0x04, 0x04, 0x04, 0x04, 0x04, 0x10],
			"spawn_player"                  => [0x05, 0x05, 0x05, 0x05, 0x05, 0x05, 0x0C],
			"entity_animation"              => [0x06, 0x06, 0x06, 0x06, 0x06, 0x06, 0x0B],
			"statistics"                    => [0x07, 0x07, 0x07, 0x07, 0x07, 0x07, 0x37],
			"update_block_entity"           => [0x09, 0x09, 0x09, 0x09, 0x09, 0x09, 0x35],
			"block_action"                  => [0x0A, 0x0A, 0x0A, 0x0A, 0x0A, 0x0A, 0x24],
			"block_change"                  => [0x0B, 0x0B, 0x0B, 0x0B, 0x0B, 0x0B, 0x23],
			"boss_bar"                      => [0x0C, 0x0C, 0x0C, 0x0C, 0x0C, 0x0C,   -1],
			"chat_message"                  => [0x0E, 0x0F, 0x0F, 0x0F, 0x0F, 0x0F, 0x02],
			"multi_block_change"            => [0x0F, 0x10, 0x10, 0x10, 0x10, 0x10, 0x22],
			"tab_complete"                  => [0x10, 0x0E, 0x0E, 0x0E, 0x0E, 0x0E, 0x3A],
			"confirm_transaction"           => [0x12, 0x11, 0x11, 0x11, 0x11, 0x11, 0x32],
			"close_window"                  => [0x13, 0x12, 0x12, 0x12, 0x12, 0x12, 0x2E],
			"open_window"                   => [0x14, 0x13, 0x13, 0x13, 0x13, 0x13, 0x2D],
			"window_items"                  => [0x15, 0x14, 0x14, 0x14, 0x14, 0x14, 0x30],
			"set_slot"                      => [0x17, 0x16, 0x16, 0x16, 0x16, 0x16, 0x2F],
			"set_cooldown"                  => [0x18, 0x17, 0x17, 0x17, 0x17, 0x17,   -1],
			"plugin_message"                => [0x19, 0x18, 0x18, 0x18, 0x18, 0x18, 0x3F],
			"disconnect"                    => [0x1B, 0x1A, 0x1A, 0x1A, 0x1A, 0x1A, 0x40],
			"entity_status"                 => [0x1C, 0x1B, 0x1B, 0x1B, 0x1B, 0x1B, 0x1A],
			"explosion"                     => [0x1E, 0x1C, 0x1C, 0x1B, 0x1B, 0x1B, 0x27],
			"unload_chunk"                  => [0x1F, 0x1D, 0x1D, 0x1D, 0x1D, 0x1D,   -1],
			"change_game_state"             => [0x20, 0x1E, 0x1E, 0x1E, 0x1E, 0x1E, 0x2B],
			"keep_alive_request"            => [0x21, 0x1F, 0x1F, 0x1F, 0x1F, 0x1F, 0x00],
			"chunk_data"                    => [0x22, 0x20, 0x20, 0x20, 0x20, 0x20, 0x21],
			"spawn_particle"                => [0x24, 0x22, 0x22, 0x22, 0x22, 0x22, 0x2A],
			"join_game"                     => [0x25, 0x23, 0x23, 0x23, 0x23, 0x23, 0x01],
			"map_data"                      => [0x26, 0x24, 0x24, 0x24, 0x24, 0x24, 0x34],
			"entity"                        => [0x27, 0x25, 0x25, 0x28, 0x28, 0x28, 0x14],
			"entity_relative_move"          => [0x28, 0x26, 0x26, 0x25, 0x25, 0x25, 0x15],
			"entity_look_and_relative_move" => [0x29, 0x27, 0x27, 0x26, 0x26, 0x26, 0x17],
			"entity_look"                   => [0x2A, 0x28, 0x28, 0x27, 0x27, 0x27, 0x16],
			"vehicle_move"                  => [0x2B, 0x29, 0x29, 0x29, 0x29, 0x29,   -1],
			"open_sign_editor"              => [0x2C, 0x2A, 0x2A, 0x2A, 0x2A, 0x2A, 0x36],
			"crafting_recipe_response"      => [0x2D, 0x2B,   -1,   -1,   -1,   -1,   -1],
			"set_player_abilities"          => [0x2E, 0x2C, 0x2B, 0x2B, 0x2B, 0x2B, 0x39],
			"combat_event"                  => [0x2F, 0x2D, 0x2C, 0x2C, 0x2C, 0x2C, 0x4C],
			"player_list_item"              => [0x30, 0x2E, 0x2D, 0x2D, 0x2D, 0x2D, 0x38],
			"teleport"                      => [0x32, 0x2F, 0x2E, 0x2E, 0x2E, 0x2E, 0x08],
			"use_bed"                       => [0x33, 0x30, 0x2F, 0x2F, 0x2F, 0x2F, 0x0A],
			"unlock_recipies"               => [0x34, 0x31, 0x30,   -1,   -1,   -1,   -1],
			"destroy_entities"              => [0x35, 0x32, 0x31, 0x30, 0x30, 0x30, 0x13],
			"remove_entity_effect"          => [0x36, 0x33, 0x32, 0x31, 0x31, 0x31, 0x1E],
			"resource_pack_send"            => [0x37, 0x34, 0x33, 0x32, 0x32, 0x32, 0x48],
			"respawn"                       => [0x38, 0x35, 0x34, 0x33, 0x33, 0x33, 0x07],
			"entity_head_look"              => [0x39, 0x36, 0x35, 0x34, 0x34, 0x34, 0x19],
			"select_advancement_tab"        => [0x3A, 0x37, 0x36,   -1,   -1,   -1,   -1],
			"world_border"                  => [0x3B, 0x38, 0x37, 0x35, 0x35, 0x35, 0x44],
			"camera"                        => [0x3C, 0x39, 0x38, 0x36, 0x36, 0x36, 0x43],
			"held_item_change"              => [0x3D, 0x3A, 0x39, 0x37, 0x37, 0x37, 0x09],
			"display_scoreboard"            => [0x3E, 0x3B, 0x3A, 0x38, 0x38, 0x38, 0x3D],
			"entity_metadata"               => [0x3F, 0x3C, 0x3B, 0x39, 0x39, 0x39, 0x1C],
			"attach_entity"                 => [0x40, 0x3D, 0x3C, 0x3A, 0x3A, 0x3A, 0x1B],
			"entity_velocity"               => [0x41, 0x3E, 0x3D, 0x3B, 0x3B, 0x3B, 0x12],
			"entity_equipment"              => [0x42, 0x3F, 0x3E, 0x3C, 0x3C, 0x3C, 0x04],
			"set_experience"                => [0x43, 0x40, 0x3F, 0x3D, 0x3D, 0x3D, 0x1F],
			"update_health"                 => [0x44, 0x41, 0x40, 0x3E, 0x3E, 0x3E, 0x06],
			"scoreboard_objective"          => [0x45, 0x42, 0x41, 0x3F, 0x3F, 0x3F, 0x3B],
			"set_passengers"                => [0x46, 0x43, 0x44, 0x42, 0x40, 0x40,   -1],
			"teams"                         => [0x47, 0x44, 0x43, 0x41, 0x41, 0x41, 0x3E],
			"update_score"                  => [0x48, 0x45, 0x44, 0x42, 0x42, 0x42, 0x3C],
			"spawn_position"                => [0x49, 0x46, 0x45, 0x43, 0x43, 0x43, 0x05],
			"time_update"                   => [0x4A, 0x47, 0x46, 0x44, 0x44, 0x44, 0x03],
			"title"                         => [0x4B, 0x48, 0x47, 0x45, 0x45, 0x45, 0x45],
			"stop_sound"                    => [0x4C,   -1,   -1,   -1,   -1,   -1,   -1],
			"sound_effect"                  => [0x4D, 0x49, 0x48, 0x46, 0x46, 0x46,   -1],
			"player_list_header_and_footer" => [0x4E, 0x4A, 0x49, 0x47, 0x47, 0x48, 0x47],
			"collect_item"                  => [0x4F, 0x4B, 0x4A, 0x48, 0x48, 0x49, 0x0D],
			"entity_teleport"               => [0x50, 0x4C, 0x4B, 0x49, 0x49, 0x4A, 0x18],
			"advancements"                  => [0x51, 0x4D, 0x4C,   -1,   -1,   -1,   -1],
			"entity_properties"             => [0x52, 0x4E, 0x4D, 0x4A, 0x4A, 0x4B, 0x20],
			"entity_effect"                 => [0x53, 0x4F, 0x4E, 0x4B, 0x4B, 0x4C, 0x1D],
			"declare_recipes"               => [0x54,   -1,   -1,   -1,   -1,   -1,   -1],
			"tags"                          => [0x55,   -1,   -1,   -1,   -1,   -1,   -1],
		];
	}

	private static function serverboundPackets()
	{
		return [
			"teleport_confirm"              => [0x00, 0x00, 0x00, 0x00, 0x00, 0x00,   -1],
			"prepare_crafting_grid"         => [  -1,   -1, 0x01,   -1,   -1,   -1,   -1],
			"tab_complete_respond"          => [0x05, 0x01, 0x02, 0x01, 0x01, 0x01, 0x14],
			"send_chat_message"             => [0x02, 0x02, 0x03, 0x02, 0x02, 0x02, 0x01],
			"client_status"                 => [0x03, 0x03, 0x04, 0x03, 0x03, 0x03, 0x16],
			"client_settings"               => [0x04, 0x04, 0x05, 0x04, 0x04, 0x04, 0x15],
			"confirm_transaction_response"  => [0x06, 0x05, 0x06, 0x05, 0x05, 0x05, 0x0F],
			"enchant_item"                  => [0x07, 0x06, 0x07, 0x06, 0x06, 0x06, 0x11],
			"click_window"                  => [0x08, 0x07, 0x08, 0x07, 0x07, 0x07, 0x0E],
			"window_closed"                 => [0x09, 0x08, 0x09, 0x08, 0x08, 0x08, 0x0D],
			"send_plugin_message"           => [0x0A, 0x09, 0x0A, 0x09, 0x09, 0x09, 0x17],
			"use_entity"                    => [0x0D, 0x0A, 0x0B, 0x0A, 0x0A, 0x0A, 0x02],
			"keep_alive_response"           => [0x0E, 0x0B, 0x0C, 0x0B, 0x0B, 0x0B, 0x00],
			"player"                        => [0x0F, 0x0C, 0x0D, 0x0F, 0x0F, 0x0F, 0x03],
			"player_position"               => [0x10, 0x0D, 0x0E, 0x0C, 0x0C, 0x0C, 0x04],
			"player_position_and_look"      => [0x11, 0x0E, 0x0F, 0x0D, 0x0D, 0x0D, 0x06],
			"player_look"                   => [0x12, 0x0F, 0x10, 0x0E, 0x0E, 0x0E, 0x05],
			"craft_recipe_request"          => [0x16, 0x12,   -1,   -1,   -1,   -1,   -1],
			"player_abilities"              => [0x17, 0x13, 0x13, 0x12, 0x12, 0x12, 0x13],
			"player_digging"                => [0x18, 0x14, 0x14, 0x13, 0x13, 0x13, 0x07],
			"entity_action"                 => [0x19, 0x15, 0x15, 0x14, 0x14, 0x14, 0x0B],
			"recipe_book_data"              => [0x1B, 0x17, 0x17,   -1,   -1,   -1,   -1],
			"resource_pack_status"          => [0x1D, 0x18, 0x18, 0x16, 0x16, 0x16, 0x19],
			"advancement_tab"               => [0x1E, 0x19, 0x19,   -1,   -1,   -1,   -1],
			"held_item_changed"             => [0x21, 0x1A, 0x1A, 0x17, 0x17, 0x17, 0x09],
			"creative_inventory_action"     => [0x24, 0x1B, 0x1B, 0x18, 0x18, 0x18, 0x10],
			"update_sign"                   => [0x26, 0x1C, 0x1C, 0x19, 0x19, 0x19, 0x12],
			"animation"                     => [0x27, 0x1D, 0x1D, 0x1A, 0x1A, 0x1A, 0x0A],
			"player_block_placement"        => [0x29, 0x1F, 0x1F, 0x1C, 0x1C, 0x1C, 0x08],
			"use_item"                      => [0x2A, 0x20, 0x20, 0x1D, 0x1D, 0x1D,   -1],
		];
	}

	/**
	 * The name of the packet.
	 * @var string $name
	 */
	public $name;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 */
	function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the id of the packet name for the given protocol version.
	 * @param string $name The name of the packet.
	 * @param integer $protocol_version
	 * @return integer -1 if not applicable for protocol version or null if the packet is unknown.
	 */
	static function getId($name, $protocol_version)
	{
		$clientbound_packet_ids = Packet::clientboundPackets();
		$serverbound_packet_ids = Packet::serverboundPackets();
		if($protocol_version >= 393)
		{
			return isset($clientbound_packet_ids[$name][0]) ? $clientbound_packet_ids[$name][0] : (isset($serverbound_packet_ids[$name][0]) ? $serverbound_packet_ids[$name][0] : null);
		}
		else if($protocol_version >= 336)
		{
			return isset($clientbound_packet_ids[$name][1]) ? $clientbound_packet_ids[$name][1] : (isset($serverbound_packet_ids[$name][1]) ? $serverbound_packet_ids[$name][1] : null);
		}
		else if($protocol_version >= 328)
		{
			return isset($clientbound_packet_ids[$name][2]) ? $clientbound_packet_ids[$name][2] : (isset($serverbound_packet_ids[$name][2]) ? $serverbound_packet_ids[$name][2] : null);
		}
		else if($protocol_version >= 314)
		{
			return isset($clientbound_packet_ids[$name][3]) ? $clientbound_packet_ids[$name][3] : (isset($serverbound_packet_ids[$name][3]) ? $serverbound_packet_ids[$name][3] : null);
		}
		else if($protocol_version >= 110)
		{
			return isset($clientbound_packet_ids[$name][4]) ? $clientbound_packet_ids[$name][4] : (isset($serverbound_packet_ids[$name][4]) ? $serverbound_packet_ids[$name][4] : null);
		}
		else if($protocol_version >= 107)
		{
			return isset($clientbound_packet_ids[$name][5]) ? $clientbound_packet_ids[$name][5] : (isset($serverbound_packet_ids[$name][5]) ? $serverbound_packet_ids[$name][5] : null);
		}
		return isset($clientbound_packet_ids[$name][6]) ? $clientbound_packet_ids[$name][6] : (isset($serverbound_packet_ids[$name][6]) ? $serverbound_packet_ids[$name][6] : null);
	}

	/**
	 * Returns the id of this packet for the given protocol version.
	 * @param integer $protocol_version
	 * @return integer -1 if not applicable for protocol version or null if the packet is unknown.
	 */
	function idFor($protocol_version)
	{
		return Packet::getId($this->name, $protocol_version);
	}

	private static function extractPacketNameFromList($list, $id, $protocol_version)
	{
		foreach($list as $n => $v)
		{
			if($protocol_version >= 393)
			{
				if($v[0] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 336)
			{
				if($v[1] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 328)
			{
				if($v[2] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 314)
			{
				if($v[3] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 110)
			{
				if($v[4] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 107)
			{
				if($v[5] == $id)
				{
					return $n;
				}
			}
			else if($v[6] == $id)
			{
				return $n;
			}
		}
		return null;
	}

	/**
	 * Converts a clientbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 */
	static function clientboundPacketIdToName($id, $protocol_version)
	{
		return Packet::extractPacketNameFromList(Packet::clientboundPackets(), $id, $protocol_version);
	}

	/**
	 * Converts a serverbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 */
	static function serverboundPacketIdToName($id, $protocol_version)
	{
		return Packet::extractPacketNameFromList(Packet::serverboundPackets(), $id, $protocol_version);
	}

	/**
	 * Instanciates an object for the given packet name.
	 * @return Packet Null if a class is not available.
	 */
	static function instanceFromName($name)
	{
		switch($name)
		{
			case "join_game":
			return new \Phpcraft\JoinGamePacket();

			case "keep_alive_request":
			return new \Phpcraft\KeepAliveRequestPacket();

			case "keep_alive_response":
			return new \Phpcraft\KeepAliveResponsePacket();

			case "map_data":
			return new \Phpcraft\MapDataPacket();

			case "set_slot";
			return new \Phpcraft\SetSlotPacket();
		}
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	abstract static function read(\Phpcraft\Connection $con);

	/**
	 * Sends the packet over the given Connection or simply writes it into the write buffer if the connection was initialised without a stream.
	 * @param Connection $con
	 */
	abstract function send(\Phpcraft\Connection $con);
}
