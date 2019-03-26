<?php
namespace Phpcraft;
class SetExperiencePacket extends Packet
{
	/**
	 * How many percent the experience bar is filled from 0.00 to 1.00.
	 * @param float $percent
	 */
	public $percent;
	/**
	 * @param integer $level
	 */
	public $level;

	/**
	 * @param float $percent How many percent the experience bar is filled from 0.00 to 1.00.
	 * @param integer $level
	 */
	public function __construct($percent = 0.00, $level = 0)
	{
		$this->percent = $percent;
		$this->level = $level;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return SetExperiencePacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		$packet = new SetExperiencePacket();
		$packet->percent = $con->readFloat();
		$packet->level = $con->readVarInt();
		$con->readVarInt(); // Total Experience
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @return void
	 * @throws Exception
	 */
	public function send(Connection $con)
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

	public function toString()
	{
		return "{SetExperiencePacket: Level ".$this->level.", ".($this->percent * 100)."% to next}";
	}
}
