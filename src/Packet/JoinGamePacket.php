<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Enum\Difficulty, Enum\Dimension, Enum\Gamemode, Exception\IOException};
/** The first packet sent to the client after they've logged in. */
class JoinGamePacket extends Packet
{
	/**
	 * @var GMP $eid
	 */
	public $eid = 0;
	public $gamemode = Gamemode::SURVIVAL;
	public $hardcore = false;
	public $dimension = Dimension::OVERWORLD;
	public $difficulty = Difficulty::PEACEFUL;
	public $render_distance = 8;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return JoinGamePacket
	 * @throws IOException
	 */
	static function read(Connection $con): JoinGamePacket
	{
		$packet = new JoinGamePacket();
		$packet->eid = $con->readInt();
		$packet->gamemode = $con->readByte();
		if($packet->gamemode >= 0x8)
		{
			$packet->gamemode -= 0x8;
			$packet->hardcore = true;
		}
		$packet->dimension = $con->protocol_version > 107 ? gmp_intval($con->readInt()) : $con->readByte();
		if($con->protocol_version < 472)
		{
			$packet->difficulty = $con->readByte();
		}
		$con->ignoreBytes(1); // Max Players (Byte)
		$con->ignoreBytes(gmp_intval($con->readVarInt())); // Level Type (String)
		if($con->protocol_version >= 472)
		{
			$packet->render_distance = gmp_intval($con->readVarInt()); // Render Distance
		}
		$con->ignoreBytes(1); // Reduced Debug Info (Boolean)
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		$con->startPacket("join_game");
		$con->writeInt($this->eid);
		$gamemode = $this->gamemode;
		if($this->hardcore)
		{
			$gamemode += 0x8;
		}
		$con->writeByte($gamemode);
		if($con->protocol_version >= 108)
		{
			$con->writeInt($this->dimension);
		}
		else
		{
			$con->writeByte($this->dimension);
		}
		if($con->protocol_version < 472)
		{
			$con->writeByte($this->difficulty);
		}
		$con->writeByte(100); // Max Players
		$con->writeString(""); // Level Type
		if($con->protocol_version >= 472)
		{
			$con->writeVarInt($this->render_distance); // Render Distance
		}
		$con->writeBoolean(false); // Reduced Debug Info
		$con->send();
		if($con->protocol_version >= 472)
		{
			$con->startPacket("difficulty");
			$con->writeUnsignedByte($this->difficulty);
			$con->writeBoolean(true); // Locked
			$con->send();
		}
	}

	function __toString()
	{
		return "{JoinGamePacket: Entity ID ".$this->eid.", Gamemode ".$this->gamemode.", ".($this->hardcore ? "Not " : "")."Hardcore Mode, Dimension ".$this->dimension.", Difficulty ".$this->difficulty."}";
	}
}
