<?php
namespace Phpcraft;
class SpawnMobPacket extends Packet
{
	/**
	 * The entity ID of the mob.
	 * @var integer $eid
	 */
	public $eid;
	/**
	 * The type of mob.
	 * @var EntityType $type
	 */
	public $type;
	/**
	 * The position of the mob.
	 * @var Position $pos
	 */
	public $pos;
	/**
	 * The entity metadata of the mob.
	 * @var EntityMetadata $metadata
	 */
	public $metadata;
	// TODO: Yaw, Pitch, Head Pitch & Velocity

	function __construct($eid = 0, $type = null)
	{
		$this->eid = $eid;
		if($type)
		{
			$this->type = $type;
			$this->metadata = $type->getMetadata();
		}
		else
		{
			$this->metadata = new \Phpcraft\EntityBase();
		}
	}

	/**
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		$packet = new \Phpcraft\SpawnMobPacket(
			$con->readVarInt(),
			$con->protocol_version >= 353 ? EntityType::get($con->readByte()) : EntityType::getLegacy($con->readByte())
		);
		$packet->pos = $con->readFixedPointPosition();
		$con->ignoreBytes(9); // Yaw, Pitch, Head Pitch & Velocity
		$packet->metadata->read($con);
		return $packet;
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(\Phpcraft\Connection $con)
	{
		$con->startPacket("spawn_mob");
		$con->writeVarInt($this->eid);
		if($con->protocol_version >= 353)
		{
			$con->writeByte($this->type->id);
		}
		else
		{
			$con->writeByte($this->type->legacy_id);
		}
		$con->writeFixedPointPosition($this->pos);
		$con->writeByte(0); // Yaw
		$con->writeByte(0); // Pitch
		$con->writeByte(0); // Head Pitch
		$con->writeShort(0); // Velocity X
		$con->writeShort(0); // Velocity Y
		$con->writeShort(0); // Velocity Z
		$this->metadata->write($con);
		$con->send();
	}

	function toString()
	{
		$str = "{SpawnMobPacket: ";
		if($this->type)
		{
			$str .= $this->type->name.", ";
		}
		return $str."Entity ID ".$this->eid.", Position ".$this->pos->toString().", ".$this->metadata->toString()."}";
	}
}
