<?php
namespace Phpcraft;
/** Sent by the server to the client to make sure it's still connected. */
class KeepAliveRequestPacket extends Packet
{
	public $keepAliveId;

	/**
	 * @param integer $keepAliveId The identifier of this keep alive packet. Defaults to current time.
	 */
	public function __construct(int $keepAliveId = null)
	{
		$this->keepAliveId = ($keepAliveId ? $keepAliveId : time());
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return KeepAliveRequestPacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		return new KeepAliveRequestPacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws Exception
	 */
	public function send(Connection $con)
	{
		$con->startPacket("keep_alive_request");
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

	/**
	 * Generates the response packet which the client should send.
	 * @return KeepAliveResponsePacket
	 */
	public function getResponse()
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}

	public function __toString()
	{
		return "{Keep Alive Request: ID ".$this->keepAliveId."}";
	}
}
