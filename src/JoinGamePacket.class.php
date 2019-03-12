<?php
namespace Phpcraft;
/** The first packet sent to the client after they've logged in. */
class JoinGamePacket extends Packet
{
	public $eid;
	public $gamemode = 0;
	public $hardcore = false;
	public $dimension = 0;
	public $difficulty = 0;

	/**
	 * @copydoc Packet::read
	 */
	static function read(\Phpcraft\Connection $con)
	{
		$packet = new JoinGamePacket();
		$packet->eid = $con->readInt();
		$packet->gamemode = $con->readByte(true);
		if($packet->gamemode > 0x8)
		{
			$packet->gamemode -= 0x8;
			$packet->hardcore = true;
		}
		$packet->dimension = $con->protocol_version > 107 ? $con->readInt() : $con->readByte();
		$packet->difficulty = $con->readByte(true);
		$con->ignoreBytes(1); // Max Players (Byte)
		$con->ignoreBytes($con->readVarInt()); // Level Type (String)
		$con->ignoreBytes(1); // Reduced Debug Info (Boolean)
		return $packet;
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(\Phpcraft\Connection $con)
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
		$con->writeByte($this->difficulty, true);
		$con->writeByte(100, true); // Max Players
		$con->writeString(""); // Level Type
		$con->writeBoolean(false); // Reduced Debug Info
		$con->send();
	}

	function toString()
	{
		return "{JoinGamePacket: Entity ID ".$this->eid.", Gamemode ".$this->gamemode.", ".($hardcore ? "Not " : "")."Hardcore Mode, Dimension ".$this->dimension.", Difficulty ".$this->difficulty."}";
	}
}
