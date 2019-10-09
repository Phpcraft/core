<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
class DestroyEntitiesPacket extends Packet
{
	/**
	 * An array of the IDs of the entities to be destroyed.
	 *
	 * @var array<int> $eids
	 */
	public $eids = [];

	/**
	 * @param array<int> $eids An array of the IDs of the entities to be destroyed.
	 */
	function __construct(array $eids = [])
	{
		$this->eids = $eids;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return DestroyEntitiesPacket
	 * @throws IOException
	 */
	static function read(Connection $con): DestroyEntitiesPacket
	{
		$packet = new DestroyEntitiesPacket();
		for($i = gmp_intval($con->readVarInt()); $i > 0; $i--)
		{
			array_push($packet->eids, gmp_intval($con->readVarInt()));
		}
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
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
		$str = "{DestroyEntitiesPacket:";
		foreach($this->eids as $eid)
		{
			$str .= " ".$eid;
		}
		return $str."}";
	}
}
