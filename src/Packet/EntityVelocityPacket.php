<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Exception\IOException};
class EntityVelocityPacket extends EntityPacket
{
	// TODO: Understand what exacly the velocity values mean.
	// According to wiki.vg, the unit is 1 / 8000 blocks per tick, but that's only half the story, as the velocity decreases over time.
	/**
	 * @var int $x
	 */
	public $x;
	/**
	 * @var int $y
	 */
	public $y;
	/**
	 * @var int $z
	 */
	public $z;

	/**
	 * EntityVelocityPacket constructor.
	 *
	 * @param array<GMP>|GMP|int|string $eids A single entity ID or an array of entity IDs.
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	function __construct($eids = [], int $x = null, int $y = null, int $z = null)
	{
		parent::__construct($eids);
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return EntityVelocityPacket
	 * @throws IOException
	 */
	static function read(Connection $con): EntityVelocityPacket
	{
		return new EntityVelocityPacket($con->readVarInt(), $con->readShort(), $con->readShort(), $con->readShort());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		foreach($this->eids as $eid)
		{
			$con->startPacket("entity_velocity");
			$con->writeVarInt($eid);
			$con->writeShort($this->x);
			$con->writeShort($this->y);
			$con->writeShort($this->z);
			$con->send();
		}
	}

	function __toString()
	{
		return "{EntityVelocityPacket: Entit".(count($this->eids) == 1 ? "y" : "ies")." ".join(", ", $this->eids)." Velocity ".$this->x." ".$this->y." ".$this->z."}";
	}
}
