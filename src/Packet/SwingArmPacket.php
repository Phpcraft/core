<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
/** Sent by the client to the server when swinging an arm. */
class SwingArmPacket extends Packet
{
	/**
	 * True when the offhand is being swung.
	 *
	 * @var bool
	 */
	public $off_hand = false;

	/**
	 * @param bool $off_hand True when the offhand is being swung.
	 */
	function __construct(bool $off_hand = false)
	{
		$this->off_hand = $off_hand;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return SwingArmPacket
	 * @throws IOException
	 */
	static function read(Connection $con): SwingArmPacket
	{
		$packet = new SwingArmPacket(false);
		if($con->protocol_version > 47 && $con->readBoolean())
		{
			$packet->off_hand = true;
		}
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @return void
	 * @throws IOException
	 */
	function send(Connection $con): void
	{
		$con->startPacket("swing_arm");
		if($con->protocol_version > 47)
		{
			$con->writeBoolean($this->off_hand);
		}
		$con->send();
	}

	function __toString()
	{
		return "{SwingArmPacket: ".($this->off_hand ? "Offhand" : "Main arm")."}";
	}
}
