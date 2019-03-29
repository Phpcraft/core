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
	 * The UUID of the entity.
	 * @var UUID $uuid
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

	/**
	 * @param integer $eid The entity ID of the mob.
	 * @param EntityType $type The type of mob.
	 * @param UUID $uuid The UUID of the entity.
	 */
	public function __construct(int $eid = 0, EntityType $type = null, UUID $uuid = null)
	{
		$this->eid = $eid;
		if($type)
		{
			$this->type = $type;
			$this->metadata = $type->getMetadata();
		}
		else
		{
			$this->metadata = new EntityBase();
		}
		if($uuid)
		{
			$this->uuid = $uuid;
		}
		else
		{
			$this->uuid = Uuid::v4();
		}
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return SpawnMobPacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		$eid = $con->readVarInt();
		if($con->protocol_version >= 49)
		{
			$uuid = $con->readUUID();
		}
		else
		{
			$uuid = null;
		}
		if($con->protocol_version >= 301)
		{
			$type = EntityType::getById($con->readVarInt(), $con->protocol_version);
		}
		else
		{
			$type = EntityType::getById($con->readByte(), $con->protocol_version);
		}
		$packet = new SpawnMobPacket($eid, $type, $uuid);
		$packet->pos = $con->protocol_version >= 100 ? $con->readPrecisePosition() : $con->readFixedPointPosition();
		$con->ignoreBytes(9); // Yaw, Pitch, Head Pitch & Velocity
		$packet->metadata->read($con);
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws Exception
	 */
	public function send(Connection $con)
	{
		$con->startPacket("spawn_mob");
		$con->writeVarInt($this->eid);
		if($con->protocol_version >= 49)
		{
			$con->writeUuid($this->uuid);
		}
		if($con->protocol_version >= 301)
		{
			$con->writeVarInt($this->type->getId($con->protocol_version));
		}
		else
		{
			$con->writeByte($this->type->getId($con->protocol_version));
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

	public function __toString()
	{
		$str = "{SpawnMobPacket: ";
		if($this->type)
		{
			$str .= $this->type->name.", ";
		}
		return $str."Entity ID ".$this->eid.", ".$this->pos->__toString().", ".$this->metadata->__toString()."}";
	}
}
