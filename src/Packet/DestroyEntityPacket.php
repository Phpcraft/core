<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
class DestroyEntityPacket extends EntityPacket
{
	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return DestroyEntityPacket
	 * @throws IOException
	 */
	static function read(Connection $con): DestroyEntityPacket
	{
		$packet = new DestroyEntityPacket();
		for($i = gmp_intval($con->readVarInt()); $i > 0; $i--)
		{
			array_push($packet->eids, gmp_intval($con->readVarInt()));
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
		$con->startPacket("destroy_entities");
		$con->writeVarInt(count($this->eids));
		foreach($this->eids as $eid)
		{
			$con->writeVarInt($eid);
		}
		$con->send();
	}

	function __toString()
	{
		return "{DestroyEntityPacket: Entit".(count($this->eids) == 1 ? "y" : "ies")." ".join(", ", $this->eids)."}";
	}
}
