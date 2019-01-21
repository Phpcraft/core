<?php
namespace Phpcraft;
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
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return (new KeepAliveResponsePacket())->_read($con);
	}
}