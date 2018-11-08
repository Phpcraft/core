<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/ChatPacket.class.php";
/** Sent by the server to the client when it's closing the connection with a chat object as reason. */
class DisconnectPacket extends ChatPacket
{
	/**
	 * The constructor.
	 * @param string $message The disconnect reason; chat object.
	 */
	function __construct($message = [])
	{
		parent::__construct("disconnect", $message);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return (new DisconnectPacket())->_read($con);
	}
}
