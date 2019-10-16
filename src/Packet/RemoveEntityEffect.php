<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, EffectType, Exception\IOException};
/** Sent by servers to clients to inform them about entities losing a potion effect. */
class RemoveEntityEffect extends EntityPacket
{
	/**
	 * @var EffectType $effect
	 */
	public $effect;

	/**
	 * @param array<int>|int $eids A single entity ID or an int array of entity IDs.
	 * @param EffectType $effect
	 */
	function __construct($eids, EffectType $effect)
	{
		parent::__construct($eids);
		$this->effect = $effect;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return RemoveEntityEffect
	 * @throws IOException
	 */
	static function read(Connection $con): RemoveEntityEffect
	{
		return new RemoveEntityEffect(gmp_intval($con->readVarInt()), EffectType::getById($con->readByte(), $con->protocol_version));
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		foreach($this->eids as $eid)
		{
			$con->startPacket("remove_entity_effect");
			$con->writeVarInt($eid);
			$con->writeByte($this->effect->getId($con->protocol_version));
			$con->send();
		}
	}

	function __toString()
	{
		return "{RemoveEntityEffect: Entities ".join(", ", $this->eids)." Effect {$this->effect->name}}";
	}
}