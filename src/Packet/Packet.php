<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
/** The base class for packets. */
abstract class Packet
{
	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return Packet
	 * @throws IOException
	 */
	abstract public static function read(Connection $con);

	/**
	 * Returns a binary string containing the payload of the packet.
	 *
	 * @param integer $protocol_version The protocol version you'd like to get the payload for.
	 * @return string
	 * @throws IOException
	 */
	function getPayload(int $protocol_version = -1)
	{
		$con = new Connection($protocol_version);
		$this->send($con);
		$con->read_buffer = $con->write_buffer;
		gmp_intval($con->readVarInt());
		return $con->read_buffer;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	abstract function send(Connection $con);

	abstract function __toString();
}
