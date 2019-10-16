<?php
namespace Phpcraft\Packet;
/** A packet affecting one or more entities. */
abstract class EntityPacket extends Packet
{
	/**
	 * The entities' IDs.
	 *
	 * @var array<int> $eids
	 */
	public $eids;

	/**
	 * @param array<int>|int|null $eids A single entity ID, an int array of entity IDs, or null.
	 */
	function __construct($eids = null)
	{
		if(is_array($eids))
		{
			$this->eids = $eids;
		}
		else if(is_int($eids))
		{
			$this->eids = [$eids];
		}
		else
		{
			$this->eids = [];
		}
	}

	/**
	 * Replaces an entity ID in the packet, e.g. for a proxy where the downstream and upstream entity IDs for the player differ.
	 *
	 * @param int $old_eid
	 * @param int $new_eid
	 */
	function replaceEntity(int $old_eid, int $new_eid)
	{
		foreach($this->eids as $i => $eid)
		{
			if($eid == $old_eid)
			{
				$this->eids[$i] = $new_eid;
				break;
			}
		}
	}
}
