<?php
namespace Phpcraft;
use Phpcraft\Exception\IOException;
class RemoveBossBarPacket extends BossBarPacket
{
	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
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
			/** @noinspection PhpUndefinedMethodInspection */
			(new DestroyEntitiesPacket([$this->uuid->toInt() * -1]))->send($con);
		}
	}

	public function __toString()
	{
		return "{RemoveBossBarPacket: Boss Bar ".$this->uuid->__toString()."}";
	}
}
