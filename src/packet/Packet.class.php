<?php
namespace Phpcraft;
/** The base class for packets. */
abstract class Packet
{
	/**
	 * Returns a binary string containing the payload of the packet.
	 * @param integer $protocol_version The protocol version you'd like to get the payload for.
	 * @return string
	 * @throws IOException
	 */
	public function getPayload(int $protocol_version = -1)
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
	 * @throws IOException
	 */
	abstract public static function read(Connection $con);

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws IOException
	 */
	abstract public function send(Connection $con);

	abstract public function __toString();
}
