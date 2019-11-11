<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, EffectType, Exception\IOException};
/** Sent by servers to clients to inform them about entities losing a potion effect. */
class RemoveEntityEffectPacket extends EntityPacket
{
	/**
	 * @var EffectType $effect
	 */
	public $effect;

	/**
	 * @param array<GMP>|GMP|int|string $eids A single entity ID or an array of entity IDs.
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
	 * @return RemoveEntityEffectPacket
	 * @throws IOException
	 */
	static function read(Connection $con): RemoveEntityEffectPacket
	{
		return new RemoveEntityEffectPacket($con->readVarInt(), EffectType::getById($con->readByte(), $con->protocol_version));
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
		return "{RemoveEntityEffect: Entit".(count($this->eids) == 1 ? "y" : "ies")." ".join(", ", $this->eids)." Effect {$this->effect->name}}";
	}
}