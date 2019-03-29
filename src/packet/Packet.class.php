<?php
namespace Phpcraft;
/**
 * A Packet.
 * Look at the source code of this class for a list of packet names.
 */
abstract class Packet
{
	/**
	 * Returns a binary string containing the payload of the packet.
	 * @param integer $protocol_version The protocol version you'd like to get the payload for.
	 * @return string
	 * @throws Exception
	 */
	public function getPayload($protocol_version = -1)
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
	 * @throws Exception
	 */
	abstract public static function read(Connection $con);

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @return void
	 * @throws Exception
	 */
	abstract public function send(Connection $con);

	abstract public function toString();
}
