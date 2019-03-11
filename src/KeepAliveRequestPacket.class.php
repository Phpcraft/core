<?php
namespace Phpcraft;
/** Sent by the server to the client to make sure it's still connected. */
class KeepAliveRequestPacket extends Packet
{
	public $keepAliveId;

	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of this keep alive packet. Defaults to current time.
	 */
	function __construct($keepAliveId = null)
	{
		$this->keepAliveId = ($keepAliveId ? $keepAliveId : time());
	}

	/**
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return new KeepAliveRequestPacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(\Phpcraft\Connection $con)
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
	function getResponse()
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}

	function toString()
	{
		return "{Keep Alive Request: ID ".$this->keepAliveId."}";
	}
}
