<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/Packet.class.php";
require_once __DIR__."/Utils.class.php";
/** A packet that contains a chat object. */
abstract class ChatPacket extends Packet
{
	private $message;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 * @param object $message The chat object that is being sent.
	 */
	protected function __construct($name, $message)
	{
		parent::__construct($name);
		$this->message = $message;
	}

	/**
	 * Returns the chat object that is being sent.
	 * @return string
	 */
	function getMessage()
	{
		return $this->message;
	}

	/**
	 * Returns the message that is being sent as text with ANSI escape codes so it will be colorful in the console, as well.
	 * @param array $translations The translations array so translated messages look proper.
	 * @see Utils::chatToANSIText()
	 */
	function getMessageAsANSIText($translations = null)
	{
		return Utils::chatToANSIText($this->message, $translations);
	}

	/**
	 * Called by children when Packet::read() is being called.
	 * @param Connection $con
	 */
	protected function _read(\Phpcraft\Connection $con)
	{
		$this->message = json_decode($con->readString(), true);
		return $this;
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
		$con->send();
	}
}
