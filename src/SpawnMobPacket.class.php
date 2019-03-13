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
	 * The Uuid of the entity.
	 * @var Uuid $uuid
	 */
	public $uuid;
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

	/**
	 * The constructor.
	 * @param integer $eid The entity ID of the mob.
	 * @param EntityType $type The type of mob.
	 * @param Uuid $uuid The Uuid of the entity.
	 */
	function __construct($eid = 0, $type = null, $uuid = null)
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
		if($uuid)
		{
			$this->uuid = $uuid;
		}
		else
		{
			$this->uuid = \Phpcraft\Uuid::v4();
		}
	}

	/**
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		$eid = $con->readVarInt();
		if($con->protocol_version >= 49)
		{
			$uuid = $con->readUuid();
		}
		else
		{
			$uuid = null;
		}
		if($con->protocol_version >= 353)
		{
			$type = EntityType::get($con->readVarInt());
		}
		else if($con->protocol_version >= 301)
		{
			$type = EntityType::getLegacy($con->readVarInt());
		}
		else
		{
			$type = EntityType::getLegacy($con->readByte());
		}
		$packet = new \Phpcraft\SpawnMobPacket($eid, $type, $uuid);
		$packet->pos = $con->protocol_version >= 100 ? $con->readPrecisePosition() : $con->readFixedPointPosition();
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
		if($con->protocol_version >= 49)
		{
			$con->writeUuid($this->uuid);
		}
		if($con->protocol_version >= 353)
		{
			$con->writeVarInt($this->type->id);
		}
		else if($con->protocol_version >= 301)
		{
			$con->writeVarInt($this->type->legacy_id);
		}
		else
		{
			$con->writeByte($this->type->legacy_id);
		}
		if($con->protocol_version >= 100)
		{
			$con->writePrecisePosition($this->pos);
		}
		else
		{
			$con->writeFixedPointPosition($this->pos);
		}
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
