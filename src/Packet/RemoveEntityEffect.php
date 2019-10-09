<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, EffectType, Exception\IOException};
/** Sent by servers to clients to inform them about an entity losing a potion effect. */
class RemoveEntityEffect extends Packet
{
	/**
	 * The entity's ID.
	 *
	 * @var int $eid
	 */
	public $eid;
	/**
	 * @var EffectType $effect
	 */
	public $effect;

	/**
	 * @param int $eid The entity's ID.
	 * @param EffectType $effect
	 */
	function __construct(int $eid, EffectType $effect)
	{
		$this->eid = $eid;
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
		$con->startPacket("remove_entity_effect");
		$con->writeVarInt($this->eid);
		$con->writeByte($this->effect->getId($con->protocol_version));
		$con->send();
	}

	function __toString()
	{
		return "{RemoveEntityEffect: {$this->effect->name} from entity #{$this->eid}}";
	}
}