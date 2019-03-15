<?php
namespace Phpcraft;
class UpdateBossBarHealthPacket extends BossBarPacket
{
	/**
	 * The percentage the boss bar is filled, aka. the health of the boss.
	 * This should be between 0 and 1. And whilst values below 0 disconnect the client, values above 1 render additional boss bars to the right.
	 * @var float $health
	 */
	public $health = 1.0;

	/**
	 * @copydoc BossBarPacket::__construct
	 * @param float $health The percentage the boss bar is filled, aka. the health of the boss.
	 */
	function __construct($uuid = null, $health = 1.0)
	{
		$this->uuid = $uuid;
		$this->health = $health;
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(Connection $con)
	{
		if($con->protocol_version > 49)
		{
			$con->startPacket("boss_bar");
			$con->writeUuid($this->uuid);
			$con->writeVarInt(2);
			$con->writeFloat($this->health);
		}
		else
		{
			$con->startPacket("entity_metadata");
			$con->writeVarInt($this->uuid->toInt() * -1);
			$metadata = new EntityLiving();
			$metadata->health = ($this->health * 200);
			if($metadata->health < 3)
			{
				$metadata->health = 3;
			}
			$metadata->write($con);
		}
		$con->send();
	}

	function toString()
	{
		return "{UpdateBossBarHealthPacket: Boss Bar ".$this->uuid->toString().", ".($this->health * 100)."% Health}";
	}
}
