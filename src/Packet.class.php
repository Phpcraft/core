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
			"spawn_player" => [0x05, 0x05, 0x05, 0x05, 0x05, 0x05, 0x0C],
			"chat_message" => [0x0E, 0x0F, 0x0F, 0x0F, 0x0F, 0x0F, 0x02],
			"plugin_message" => [0x19, 0x18, 0x18, 0x18, 0x18, 0x18, 0x3F],
			"disconnect" => [0x1B, 0x1A, 0x1A, 0x1A, 0x1A, 0x1A, 0x40],
			"open_window" => [0x14, 0x13, 0x13, 0x13, 0x13, 0x13, 0x2D],
			"change_game_state" => [0x20, 0x1E, 0x1E, 0x1E, 0x1E, 0x1E, 0x2B],
			"keep_alive_request" => [0x21, 0x1F, 0x1F, 0x1F, 0x1F, 0x1F, 0x00],
			"join_game" => [0x25, 0x23, 0x23, 0x23, 0x23, 0x23, 0x01],
			"entity_relative_move" => [0x28, 0x26, 0x26, 0x25, 0x25, 0x25, 0x15],
			"entity_look_and_relative_move" => [0x29, 0x27, 0x27, 0x26, 0x26, 0x26, 0x17],
			"entity_look" => [0x2A, 0x28, 0x28, 0x27, 0x27, 0x27, 0x16],
			"player_list_item" => [0x30, 0x2E, 0x2D, 0x2D, 0x2D, 0x2D, 0x38],
			"teleport" => [0x32, 0x2F, 0x2E, 0x2E, 0x2E, 0x2E, 0x08],
			"destroy_entites" => [0x35, 0x32, 0x31, 0x30, 0x30, 0x30, 0x13],
			"respawn" => [0x38, 0x35, 0x34, 0x33, 0x33, 0x33, 0x07],
			"update_health" => [0x44, 0x41, 0x40, 0x3E, 0x3E, 0x3E, 0x06],
			"spawn_position" => [0x49, 0x46, 0x45, 0x43, 0x43, 0x43, 0x05],
			"time_update" => [0x4A, 0x47, 0x46, 0x44, 0x44, 0x44, 0x03],
			"player_list_header_and_footer" => [0x4E, 0x4A, 0x49, 0x47, 0x47, 0x48, 0x47],
			"entity_teleport" => [0x50, 0x4C, 0x4B, 0x49, 0x49, 0x4A, 0x18]
		];
	}

	private static function serverboundPackets()
	{
		return [
			"teleport_confirm" => [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, -1],
			"send_chat_message" => [0x02, 0x02, 0x03, 0x02, 0x02, 0x02, 0x01],
			"client_status" => [0x03, 0x03, 0x04, 0x03, 0x03, 0x03, 0x16],
			"client_settings" => [0x04, 0x04, 0x05, 0x04, 0x04, 0x04, 0x15],
			"close_window" => [0x09, 0x08, 0x09, 0x08, 0x08, 0x08, 0x0D],
			"send_plugin_message" => [0x0A, 0x09, 0x0A, 0x09, 0x09, 0x09, 0x17],
			"keep_alive_response" => [0x0E, 0x0B, 0x0C, 0x0B, 0x0B, 0x0B, 0x00],
			"player" => [0x0F, 0x0C, 0x0D, 0x0F, 0x0F, 0x0F, 0x03],
			"player_position" => [0x10, 0x0D, 0x0E, 0x0C, 0x0C, 0x0C, 0x04],
			"player_position_and_look" => [0x11, 0x0E, 0x0F, 0x0D, 0x0D, 0x0D, 0x06],
			"player_look" => [0x12, 0x0F, 0x10, 0x0E, 0x0E, 0x0E, 0x05],
			"held_item_change" => [0x21, 0x1A, 0x1A, 0x17, 0x17, 0x17, 0x09],
			"animation" => [0x27, 0x1D, 0x1D, 0x1A, 0x1A, 0x1A, 0x0A],
			"player_block_placement" => [0x29, 0x1F, 0x1F, 0x1C, 0x1C, 0x1C, 0x08],
			"use_item" => [0x2A, 0x20, 0x20, 0x1D, 0x1D, 0x1D, -1],
		];
	}

	/**
	 * The name of the packet.
	 * @var string $name
	 */
	protected $name;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 */
	protected function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the name of the packet.
	 * @return string
	 */
	function getName()
	{
		return $this->name;
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
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	abstract static function read(\Phpcraft\Connection $con);

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	abstract function send(\Phpcraft\Connection $con);
}
