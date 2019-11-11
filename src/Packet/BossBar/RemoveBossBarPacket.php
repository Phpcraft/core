<?php
namespace Phpcraft\Packet\BossBar;
use Phpcraft\
{Connection, Exception\IOException, Packet\DestroyEntityPacket};
class RemoveBossBarPacket extends BossBarPacket
{
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
			(new DestroyEntityPacket([abs($this->uuid->hashCode()) * -1]))->send($con);
		}
	}

	function __toString()
	{
		return "{RemoveBossBarPacket: Boss Bar ".$this->uuid->__toString()."}";
	}
}
