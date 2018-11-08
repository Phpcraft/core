<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/ChatPacket.class.php";
/** Sent by the server to the client when there's a new message. */
class ChatMessagePacket extends ChatPacket
{
	private $position = 0;

	/**
	 * The constructor.
	 * @param array $message The chat object that is being sent.
	 * @param integer $position 0 = player message, 1 = system message, 2 = game info (above hotbar).
	 */
	function __construct($message = [], $position = 1)
	{
		parent::__construct("chat_message", $message);
		$this->position = $position;
	}

	/**
	 * Returns the position of the message.
	 * @return integer 0 for player message, 1 for system message, or 2 for game info (above hotbar).
	 */
	function getPosition()
	{
		return $this->position;
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read(\Phpcraft\Connection $con)
	{
		return new ChatMessagePacket(json_decode($con->readString(), true), $con->readByte());
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send(\Phpcraft\Connection $con)
	{
		$con->startPacket($this->name);
		$con->writeString(json_encode($this->message));
		$con->writeByte($position);
		$con->send();
	}
}
