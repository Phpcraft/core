<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
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
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
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
