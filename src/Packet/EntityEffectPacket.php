<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, EffectType, Exception\IOException};
/** Sent by servers to clients to inform them about entities gaining a potion effect. */
class EntityEffectPacket extends EntityPacket
{
	/**
	 * @var EffectType $effect
	 */
	public $effect;
	/**
	 * The effect's amplifier = the effect's level - 1.
	 *
	 * @var int $amplifier
	 */
	public $amplifier;
	/**
	 * The effect's duration, in seconds.
	 *
	 * @var GMP|int|string
	 */
	public $duration;
	/**
	 * @var bool $particles
	 */
	public $particles;

	/**
	 * @param array<int>|int $eids A single entity ID or an int array of entity IDs.
	 * @param EffectType $effect
	 * @param int $amplifier The effect's amplifier = the effect's level - 1.
	 * @param GMP|int|string $duration The effect's duration, in seconds.
	 * @param bool $particles
	 */
	function __construct($eids, EffectType $effect, int $amplifier, $duration, bool $particles = true)
	{
		parent::__construct($eids);
		$this->effect = $effect;
		$this->amplifier = $amplifier;
		$this->duration = $duration;
		$this->particles = $particles;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return EntityEffectPacket
	 * @throws IOException
	 */
	static function read(Connection $con): EntityEffectPacket
	{
		$packet = new EntityEffectPacket(gmp_intval($con->readVarInt()), EffectType::getById($con->readByte(), $con->protocol_version), $con->readByte(), $con->readVarInt());
		if($con->protocol_version > 110)
		{
			$packet->particles = $con->readByte() | 0x02;
		}
		else
		{
			$packet->particles = !$con->readBoolean();
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
		foreach($this->eids as $eid)
		{
			$con->startPacket("entity_effect")
				->writeVarInt($eid)
				->writeByte($this->effect->getId($con->protocol_version))
				->writeByte($this->amplifier)
				->writeVarInt($this->duration);
			if($con->protocol_version > 110)
			{
				$con->writeByte($this->particles ? 0x00 : 0x02);
			}
			else
			{
				$con->writeBoolean(!$this->particles);
			}
			$con->send();
		}
	}

	function __toString()
	{
		return "{EntityEffectPacket: Entities ".join(", ", $this->eids)." Effect {$this->effect->name} Level ".($this->amplifier - 1)." Seconds ".gmp_strval($this->duration)." ".($this->particles ? "With" : "No")." Particles}";
	}
}
