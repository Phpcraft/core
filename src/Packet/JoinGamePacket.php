<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
/** The first packet sent to the client after they've logged in. */
class JoinGamePacket extends Packet
{
	public $eid = 0;
	public $gamemode = 0;
	public $hardcore = false;
	public $dimension = 0;
	public $difficulty = 0;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return JoinGamePacket
	 * @throws IOException
	 */
	public static function read(Connection $con)
	{
		$packet = new JoinGamePacket();
		$packet->eid = gmp_intval($con->readInt());
		$packet->gamemode = $con->readByte(true);
		if($packet->gamemode >= 0x8)
		{
			$packet->gamemode -= 0x8;
			$packet->hardcore = true;
		}
		$packet->dimension = $con->protocol_version > 107 ? gmp_intval($con->readInt()) : $con->readByte();
		if($con->protocol_version < 472)
		{
			$packet->difficulty = $con->readByte(true);
		}
		$con->ignoreBytes(1); // Max Players (Byte)
		$con->ignoreBytes(gmp_intval($con->readVarInt())); // Level Type (String)
		if($con->protocol_version >= 472)
		{
			gmp_intval($con->readVarInt()); // View Distance
		}
		$con->ignoreBytes(1); // Reduced Debug Info (Boolean)
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
		$con->startPacket("join_game");
		$con->writeInt($this->eid);
		$gamemode = $this->gamemode;
		if($this->hardcore)
		{
			$gamemode += 0x8;
		}
		$con->writeByte($gamemode, true);
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
		$con->writeByte(100, true); // Max Players
		$con->writeString(""); // Level Type
		if($con->protocol_version >= 472)
		{
			$con->writeVarInt(8); // View Distance
		}
		$con->writeBoolean(false); // Reduced Debug Info
		$con->send();
		if($con->protocol_version >= 472)
		{
			$con->startPacket("difficulty");
			$con->writeByte($this->difficulty);
			$con->writeBoolean(true); // Locked
			$con->send();
		}
	}

	function __toString()
	{
		return "{JoinGamePacket: Entity ID ".$this->eid.", Gamemode ".$this->gamemode.", ".($this->hardcore ? "Not " : "")."Hardcore Mode, Dimension ".$this->dimension.", Difficulty ".$this->difficulty."}";
	}
}
