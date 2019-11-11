<?php
namespace Phpcraft\Packet;
use GMP;
/** A clientbound packet affecting one or more entities. */
abstract class EntityPacket extends Packet
{
	/**
	 * The entities' IDs.
	 * When using Packet::read(), this will only ever have one entity ID, except for DestroyEntityPacket.
	 *
	 * @var array<GMP> $eids
	 */
	public $eids;

	/**
	 * @param array<GMP>|GMP|int|string $eids A single entity ID or an array of entity IDs.
	 */
	function __construct($eids = [])
	{
		if(is_array($eids))
		{
			$this->eids = $eids;
		}
		else if($eids instanceof GMP)
		{
			$this->eids = [$eids];
		}
		else if(is_int($eids) || is_string($eids))
		{
			$this->eids = [gmp_init($eids)];
		}
		else
		{
			$this->eids = [];
		}
	}

	/**
	 * Replaces an entity ID in the packet, e.g. for a proxy where the downstream and upstream entity IDs for the player differ.
	 *
	 * @param GMP $old_eid
	 * @param GMP $new_eid
	 * @return void
	 */
	function replaceEntity(GMP $old_eid, GMP $new_eid): void
	{
		foreach($this->eids as $i => $eid)
		{
			if(gmp_cmp($eid, $old_eid) == 0)
			{
				$this->eids[$i] = $new_eid;
				break;
			}
		}
	}
}
