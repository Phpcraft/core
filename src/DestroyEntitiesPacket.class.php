<?php
namespace Phpcraft;
class DestroyEntitiesPacket extends Packet
{
	/**
	 * An array of the IDs of the entities to be destroyed.
	 * @var array $eids
	 */
	public $eids = [];

	/**
	 * @param $eids integer[] An array of the IDs of the entities to be destroyed.
	 */
	public function __construct($eids = [])
	{
		$this->eids = $eids;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return DestroyEntitiesPacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		$packet = new DestroyEntitiesPacket();
		for($i = $con->readVarInt(); $i > 0; $i--)
		{
			array_push($packet->eids, $con->readVarInt());
		}
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
		$con->startPacket("destroy_entities");
		$con->writeVarInt(count($this->eids));
		foreach($this->eids as $eid)
		{
			$con->writeVarInt($eid);
		}
		$con->send();
	}

	public function toString()
	{
		$str = "{DestroyEntitiesPacket:";
		foreach($this->eids as $eid)
		{
			$str .= " ".$eid;
		}
		return $str."}";
	}
}
