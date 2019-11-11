<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
class SetExperiencePacket extends Packet
{
	/**
	 * How many percent the experience bar is filled from 0.00 to 1.00.
	 *
	 * @param float $percent
	 */
	public $percent;
	/**
	 * @param int $level
	 */
	public $level;

	/**
	 * @param float $percent How many percent the experience bar is filled from 0.00 to 1.00.
	 * @param int $level
	 */
	function __construct(float $percent = 0.00, int $level = 0)
	{
		$this->percent = $percent;
		$this->level = $level;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return SetExperiencePacket
	 * @throws IOException
	 */
	static function read(Connection $con): SetExperiencePacket
	{
		$packet = new SetExperiencePacket();
		$packet->percent = $con->readFloat();
		$packet->level = gmp_intval($con->readVarInt());
		gmp_intval($con->readVarInt()); // Total Experience
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
		$con->startPacket("set_experience");
		$con->writeFloat($this->percent);
		$con->writeVarInt($this->level);
		$con->writeVarInt(0); // Total Experience
		/* Actually calculating total experience is not needed nor useful yet, but the work's already done:
		if($this->level > 32)
		{
			$con->writeVarInt((pow($this->level, 2) * 4.5) + (162.5 * $this->level) + 2220);
		}
		else if($this->level > 16)
		{
			$con->writeVarInt((pow($this->level, 2) * 2.5) + (40.5 * $this->level) + 360);
		}
		else
		{
			$con->writeVarInt(pow($this->level, 2) + (6 * $this->level));
		}
		*/
		$con->send();
	}

	function __toString()
	{
		return "{SetExperiencePacket: Level ".$this->level.", ".($this->percent * 100)."% to next}";
	}
}
