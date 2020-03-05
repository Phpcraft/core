<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Enum\Difficulty, Exception\IOException};
/**
 * Server-to-client.
 *
 * @since 0.5.9
 */
class DifficultyPacket extends Packet
{
	/**
	 * @var int $difficulty
	 */
	public $difficulty;

	function __construct(int $difficulty)
	{
		$this->difficulty = $difficulty;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return DifficultyPacket
	 * @throws IOException
	 */
	static function read(Connection $con): DifficultyPacket
	{
		$packet = new DifficultyPacket($con->readUnsignedByte());
		if($con->protocol_version >= 472)
		{
			$con->ignoreBytes(1); // Locked (Boolean)
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
		$con->startPacket("difficulty");
		$con->writeUnsignedByte($this->difficulty);
		if($con->protocol_version >= 472)
		{
			$con->writeBoolean(true); // Locked
		}
		$con->send();
	}

	function __toString()
	{
		return "{DifficultyPacket: Difficulty ".(Difficulty::nameOf($this->difficulty) ?? $this->difficulty)."}";
	}
}