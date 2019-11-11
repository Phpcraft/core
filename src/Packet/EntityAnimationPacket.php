<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Exception\IOException};
class EntityAnimationPacket extends EntityPacket
{
	const ANIMATION_SWING_MAIN_ARM = 0;
	const ANIMATION_TAKE_DAMAGE = 1;
	const ANIMATION_LEAVE_BED = 2;
	const ANIMATION_SWING_OFFHAND = 3;
	const ANIMATION_CRITICAL_EFFECT = 4;
	const ANIMATION_MAGIC_CRITICAL_EFFECT = 5;
	/**
	 * @var int $animation
	 */
	public $animation = self::ANIMATION_SWING_MAIN_ARM;

	/**
	 * @param array<GMP>|GMP|int|string $eids A single entity ID or an array of entity IDs.
	 * @param int|null $animation
	 */
	function __construct($eids = [], int $animation = self::ANIMATION_SWING_MAIN_ARM)
	{
		parent::__construct($eids);
		$this->animation = $animation;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return EntityAnimationPacket
	 * @throws IOException
	 */
	static function read(Connection $con): EntityAnimationPacket
	{
		$packet = new EntityAnimationPacket($con->readVarInt());
		$packet->animation = $con->readUnsignedByte();
		if($packet->animation == 3 && $con->protocol_version < 49)
		{
			$packet->animation = 0;
		}
		return $packet;
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
			$con->startPacket("entity_animation");
			$con->writeVarInt($eid);
			$con->writeUnsignedByte($this->animation);
			$con->send();
		}
	}

	function __toString()
	{
		return "{EntityAnimationPacket: Entit".(count($this->eids) == 1 ? "y" : "ies")." ".join(", ", $this->eids)." ".$this->getAnimationName()."}";
	}

	/**
	 * The name of the animation in English, e.g. "swing main arm" or "critical effect"
	 *
	 * @return string
	 */
	function getAnimationName(): string
	{
		switch($this->animation)
		{
			case 0:
				return "swing main arm";
			case 1:
				return "take damage";
			case 2:
				return "leave bed";
			case 3:
				return "swing offhand";
			case 4:
				return "critical effect";
			case 5:
				return "magic critical effect";
		}
		return "unknown animation";
	}
}
