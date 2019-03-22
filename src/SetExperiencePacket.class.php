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
	 * The constructor.
	 * @param float $percent How many percent the experience bar is filled from 0.00 to 1.00.
	 * @param integer $level
	 */
	public function __construct($percent = 0.00, $level = 0)
	{
		$this->percent = $percent;
		$this->level = $level;
	}

	/**
	 * @copydoc Packet::read
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
	 * @copydoc Packet::send
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
