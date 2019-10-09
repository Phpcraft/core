<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, EffectType, Exception\IOException};
class EntityEffectPacket extends Packet
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
	 * @param int $eid The entity's ID.
	 * @param EffectType $effect
	 * @param int $amplifier The effect's amplifier = the effect's level - 1.
	 * @param GMP|int|string $duration The effect's duration, in seconds.
	 * @param bool $particles
	 */
	function __construct(int $eid, EffectType $effect, int $amplifier, $duration, bool $particles = true)
	{
		$this->eid = $eid;
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
		$con->startPacket("entity_effect")
			->writeVarInt($this->eid)
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

	function __toString()
	{
		return "{EntityEffectPacket: Entity #{$this->eid} gets {$this->effect->name} level ".($this->amplifier - 1)." for ".gmp_strval($this->duration)." seconds with".($this->particles ? "" : "out")." particles}";
	}
}
