<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Exception\IOException};
/** Sent by the client to the server in response to KeepAliveRequestPacket to ensure it is still connected. */
class KeepAliveResponsePacket extends Packet
{
	/**
	 * The identifier of this keep alive packet.
	 *
	 * @var GMP $keepAliveId
	 */
	public $keepAliveId;

	/**
	 * @param GMP|string|integer $keepAliveId The identifier of the keep alive request packet this response is for.
	 */
	function __construct($keepAliveId)
	{
		if(!$keepAliveId instanceof GMP)
		{
			$keepAliveId = gmp_init($keepAliveId);
		}
		$this->keepAliveId = $keepAliveId;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return KeepAliveResponsePacket
	 * @throws IOException
	 */
	static function read(Connection $con): KeepAliveResponsePacket
	{
		return new KeepAliveResponsePacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		$con->startPacket("keep_alive_response");
		if($con->protocol_version >= 339)
		{
			$con->writeLong($this->keepAliveId);
		}
		else
		{
			$con->writeVarInt($this->keepAliveId);
		}
		$con->send();
	}

	function __toString()
	{
		return "{Keep Alive Response: ID ".gmp_strval($this->keepAliveId)."}";
	}
}