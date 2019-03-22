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
		}
		else
		{
			$con->startPacket("destroy_entities");
			$con->writeVarInt(1);
			$con->writeVarInt($this->uuid->toInt() * -1);
		}
		$con->send();
	}

	public function toString()
	{
		return "{RemoveBossBarPacket: Boss Bar ".$this->uuid->toString()."}";
	}
}
