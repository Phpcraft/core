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
	 * @return Packet|null
	 * @throws IOException
	 */
	abstract static function read(Connection $con);

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	abstract function send(Connection $con);

	abstract function __toString();
}
