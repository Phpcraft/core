<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Enum\Difficulty, Enum\Dimension, Enum\Gamemode, Exception\IOException};
/**
 * Server-to-client.
 *
 * @since 0.5.9
 */
class RespawnPacket extends Packet
{
	/**
	 * @var int $dimension
	 */
	public $dimension = Dimension::OVERWORLD;
	/**
	 * @var int $gamemode
	 */
	public $gamemode = Gamemode::SURVIVAL;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return RespawnPacket
	 * @throws IOException
	 */
	static function read(Connection $con): RespawnPacket
	{
		$packet = new RespawnPacket();
		$packet->dimension = $con->protocol_version > 107 ? gmp_intval($con->readInt()) : $con->readByte();
		if($con->protocol_version >= 565)
		{
			$con->ignoreBytes(8); // Hashed Seed (Long)
		}
		else if($con->protocol_version < 472)
		{
			$con->ignoreBytes(1); // Difficulty (Byte)
		}
		$packet->gamemode = $con->readUnsignedByte();
		$con->readString(); // Level Type
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
		$con->startPacket("respawn");
		if($con->protocol_version >= 108)
		{
			$con->writeInt($this->dimension);
		}
		else
		{
			$con->writeByte($this->dimension);
		}
		if($con->protocol_version >= 565)
		{
			$con->writeLong(0); // Hashed Seed
		}
		else if($con->protocol_version < 472)
		{
			$con->writeUnsignedByte(Difficulty::PEACEFUL);
		}
		$con->writeUnsignedByte($this->gamemode);
		$con->writeString(""); // Level Type
		$con->send();
	}

	function __toString()
	{
		return "{RespawnPacket: Dimension ".(Dimension::nameOf($this->dimension) ?? $this->dimension).", Gamemode ".(Gamemode::nameOf($this->gamemode) ?? $this->gamemode)."}";
	}
}