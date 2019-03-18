<?php
namespace Phpcraft;
/**
 * A Packet.
 * Look at the source code of this class for a list of packet names.
 */
abstract class Packet
{
	/**
	 * Returns the id of the packet name for the given protocol version.
	 * @param string $name The name of the packet.
	 * @param integer $protocol_version
	 * @return integer null the packet is not applicable for the protocol version or unknown.
	 * @deprecated Use PacketId::get($name)->getId($protocol_version), instead.
	 */
	static function getId($name, $protocol_version)
	{
		return @PacketId::get($name)->getId($protocol_version);
	}

	/**
	 * Converts a clientbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 * @deprecated Use ClientboundPacket::getById($id)->name, instead.
	 */
	static function clientboundPacketIdToName($id, $protocol_version)
	{
		return @ClientboundPacket::getById($id, $protocol_version)->name;
	}

	/**
	 * Converts a serverbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 * @deprecated Use ServerboundPacket::getById($id)->name, instead.
	 */
	static function serverboundPacketIdToName($id, $protocol_version)
	{
		return @ServerboundPacket::getById($id, $protocol_version)->name;
	}

	/**
	 * Returns a binary string containing the payload of the packet.
	 * @param integer $protocol_version The protocol version you'd like to get the payload for.
	 * @return string
	 */
	function getPayload($protocol_version = -1)
	{
		$con = new Connection($protocol_version);
		$this->send($con);
		$con->read_buffer = $con->write_buffer;
		$con->readVarInt();
		return $con->read_buffer;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return Packet
	 */
	abstract static function read(Connection $con);

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @return void
	 */
	abstract function send(Connection $con);

	abstract function toString();

	/**
	 * Initialises the packet class with the given name by reading its payload from the given Connection.
	 * Returns null if the packet does not have a class implementation yet.
	 * @return Packet
	 */
	static function init($name, Connection $con)
	{
		switch($name)
		{
			case "boss_bar":
			return BossBarPacket::read($con);

			case "join_game":
			return JoinGamePacket::read($con);

			case "keep_alive_request":
			return KeepAliveRequestPacket::read($con);

			case "keep_alive_response":
			return KeepAliveResponsePacket::read($con);

			case "map_data":
			return MapDataPacket::read($con);

			case "set_experience":
			return SetExperiencePacket::read($con);

			case "set_slot":
			return SetSlotPacket::read($con);

			case "spawn_mob":
			return SpawnMobPacket::read($con);
		}
		return null;
	}
}
