<?php
namespace Phpcraft;
/** Sent by the client to the server in response to KeepAliveRequestPacket to ensure it is still connected. */
class KeepAliveResponsePacket extends Packet
{
	public $keepAliveId;

	/**
	 * @param integer $keepAliveId The identifier of the keep alive request packet this response is for.
	 */
	public function __construct(int $keepAliveId)
	{
		$this->keepAliveId = $keepAliveId;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return KeepAliveResponsePacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		return new KeepAliveResponsePacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws Exception
	 */
	public function send(Connection $con)
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

	public function __toString()
	{
		return "{Keep Alive Response: ID ".$this->keepAliveId."}";
	}
}