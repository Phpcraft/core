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
	 * The constructor.
	 * @param array $eids An array of the IDs of the entities to be destroyed.
	 */
	public function __construct($eids = [])
	{
		$this->eids = $eids;
	}

	/**
	 * @copydoc Packet::read
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
	 * @copydoc Packet::send
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
