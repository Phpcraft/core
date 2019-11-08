<?php
namespace Phpcraft\Packet;
use GMP;
use hellsh\UUID;
use Phpcraft\
{Connection, Entity\Base, Entity\EntityType, Entity\Metadata, Exception\IOException, Point3D};
class SpawnMobPacket extends Packet
{
	/**
	 * The entity ID of the mob.
	 *
	 * @var GMP $eid
	 */
	public $eid;
	/**
	 * The UUID of the entity.
	 *
	 * @var UUID $uuid
	 */
	public $uuid;
	/**
	 * The type of mob.
	 *
	 * @var EntityType $type
	 */
	public $type;
	/**
	 * The position of the mob.
	 *
	 * @var Point3D $pos
	 */
	public $pos;
	/**
	 * The mob's rotation on the X axis, 0 to 359.9.
	 *
	 * @var float $yaw
	 */
	public $yaw = 0;
	/**
	 * The mob's rotation on the Y axis, -90 to 90.
	 *
	 * @var float $pitch
	 */
	public $pitch = 0;
	/**
	 * The entity metadata of the mob.
	 *
	 * @var Metadata $metadata
	 */
	public $metadata;

	/**
	 * @param GMP|int|string $eid The entity ID of the mob.
	 * @param EntityType|null $type The type of mob.
	 * @param UUID|null $uuid The UUID of the entity.
	 */
	function __construct($eid = 0, EntityType $type = null, UUID $uuid = null)
	{
		$this->eid = $eid instanceof GMP ? $eid : gmp_init($eid);
		if($type)
		{
			$this->type = $type;
			$this->metadata = $type->getMetadata();
		}
		else
		{
			$this->metadata = new Base();
		}
		$this->uuid = $uuid ?? Uuid::v4();
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return SpawnMobPacket
	 * @throws IOException
	 */
	static function read(Connection $con): SpawnMobPacket
	{
		$eid = gmp_intval($con->readVarInt());
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
			$type = EntityType::getById(gmp_intval($con->readVarInt()), $con->protocol_version);
		}
		else
		{
			$type = EntityType::getById($con->readByte(), $con->protocol_version);
		}
		$packet = new SpawnMobPacket($eid, $type, $uuid);
		$packet->pos = $con->protocol_version >= 100 ? $con->readPrecisePosition() : $con->readFixedPointPosition();
		$packet->yaw = $con->readAngle();
		$packet->pitch = $con->readAngle();
		$con->ignoreBytes(7); // Head Pitch + Velocity
		$packet->metadata->read($con);
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
		$con->writeAngle($this->yaw);
		$con->writeAngle($this->pitch);
		$con->writeAngle($this->pitch); // Head Pitch
		$con->writeShort(0); // Velocity X
		$con->writeShort(0); // Velocity Y
		$con->writeShort(0); // Velocity Z
		$this->metadata->write($con);
		$con->send();
	}

	function __toString()
	{
		$str = "{SpawnMobPacket: ";
		if($this->type)
		{
			$str .= $this->type->name.", ";
		}
		return $str."Entity ID ".$this->eid.", ".$this->pos->__toString().", ".$this->metadata->__toString()."}";
	}
}
