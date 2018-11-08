<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/Packet.class.php";
/** Sent by the client to the server when it wants to send a message or execute a command. */
class SendChatMessagePacket extends Packet
{
	private $message;

	/**
	 * The constructor.
	 * @param string $message The message you want to send; not a chat object.
	 */
	function __construct($message)
	{
		parent::__construct("send_chat_message");
		$this->message = $message;
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return new SendMessagePacket($con->readString(256));
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send(\Phpcraft\Connection $con)
	{
		$con->startPacket($this->name);
		$con->writeString($this->message);
		$con->send();
	}
}
