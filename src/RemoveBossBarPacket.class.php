<?php
namespace Phpcraft;
class RemoveBossBarPacket extends BossBarPacket
{
	/**
	 * @copydoc Packet::send
	 */
	public function send(Connection $con)
	{
		if($con->protocol_version > 49)
		{
			$con->startPacket("boss_bar");
			$con->writeUUID($this->uuid);
			$con->writeVarInt(1);
			$con->send();
		}
		else
		{
			(new DestroyEntitiesPacket([$this->uuid->toInt() * -1]))->send($con);
		}
	}

	public function toString()
	{
		return "{RemoveBossBarPacket: Boss Bar ".$this->uuid->toString()."}";
	}
}
