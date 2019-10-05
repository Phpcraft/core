<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
class ClientboundAbilitiesPacket extends Packet
{
	/**
	 * @var boolean $invulnerable
	 */
	public $invulnerable = false;
	/**
	 * @var boolean $flying
	 */
	public $flying = false;
	/**
	 * @var boolean $can_fly
	 */
	public $can_fly = false;
	/**
	 * @var boolean $instant_breaking
	 */
	public $instant_breaking = false;
	/**
	 * @var float $fly_speed
	 */
	public $fly_speed = 0.05;
	/**
	 * @var float $walk_speed
	 */
	public $walk_speed = 0.1;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return Packet
	 * @throws IOException
	 */
	static function read(Connection $con): Packet
	{
		$packet = new ClientboundAbilitiesPacket();
		$flags = $con->readByte();
		$packet->instant_breaking = (($flags & 0x08) != 0);
		$packet->can_fly = (($flags & 0x04) != 0);
		$packet->flying = (($flags & 0x02) != 0);
		$packet->invulnerable = (($flags & 0x01) != 0);
		$packet->fly_speed = $con->readFloat();
		$packet->walk_speed = $con->readFloat();
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
		$con->startPacket("clientbound_abilities");
		$flags = 0x00;
		if($this->invulnerable)
		{
			$flags |= 0x01;
		}
		if($this->flying)
		{
			$flags |= 0x02;
		}
		if($this->can_fly)
		{
			$flags |= 0x04;
		}
		if($this->instant_breaking)
		{
			$flags |= 0x08;
		}
		$con->writeByte($flags);
		$con->writeFloat($this->fly_speed);
		$con->writeFloat($this->walk_speed);
		$con->send();
	}

	function __toString()
	{
		return "{ClientboundAbilitiesPacket: ".($this->invulnerable ? "" : "Not ")."Invulnerable, ".($this->flying ? "" : "Not ")."Flying, Can".($this->can_fly ? "" : "'t")." Fly, ".($this->instant_breaking ? "" : "No ")." Instant Breaking, Fly Speed ".($this->fly_speed / 0.05)."x, Walk Speed ".($this->walk_speed / 0.1)."}";
	}
}
