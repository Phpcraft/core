<?php
namespace Phpcraft\Packet\BossBar;
use Phpcraft\
{Connection, Exception\IOException, Packet\DestroyEntitiesPacket};
class RemoveBossBarPacket extends BossBarPacket
{
	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
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
			(new DestroyEntitiesPacket([abs($this->uuid->hashCode()) * -1]))->send($con);
		}
	}

	function __toString()
	{
		return "{RemoveBossBarPacket: Boss Bar ".$this->uuid->__toString()."}";
	}
}
