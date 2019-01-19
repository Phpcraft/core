<?php
namespace Phpcraft;
require_once __DIR__."/KeepAliveResponsePacket.class.php";
/** Sent by the server to the client to make sure it's still connected. */
class KeepAliveRequestPacket extends KeepAlivePacket
{
	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_request", $keepAliveId);
	}

	/**
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return (new KeepAliveRequestPacket())->_read($con);
	}

	/**
	 * Generates the response packet which the client should send.
	 * @return KeepAliveResponsePacket
	 */
	function getResponse()
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}
}
