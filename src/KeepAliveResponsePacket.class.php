<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/KeepAlivePacket.class.php";
/** Sent by the client to the server in response to KeepAliveRequestPacket. */
class KeepAliveResponsePacket extends KeepAlivePacket
{
	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_response", $keepAliveId);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return (new KeepAliveResponsePacket())->_read($con);
	}
}