<?php
namespace Phpcraft;
/** Sent by the client to the server in response to KeepAliveRequestPacket to ensure it is still connected. */
class KeepAliveResponsePacket extends Packet
{
	public $keepAliveId;

	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of the keep alive request packet this response is for.
	 */
	public function __construct($keepAliveId)
	{
		$this->keepAliveId = $keepAliveId;
	}

	/**
	 * @copydoc Packet::read
	 */
	public static function read(Connection $con)
	{
		return new KeepAliveResponsePacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * @copydoc Packet::send
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

	public function toString()
	{
		return "{Keep Alive Response: ID ".$this->keepAliveId."}";
	}
}