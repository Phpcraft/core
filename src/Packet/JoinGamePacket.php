<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Enum\Difficulty, Enum\Dimension, Enum\Gamemode, Exception\IOException, NBT\ByteArrayTag, NBT\ByteTag, NBT\CompoundTag, NBT\FloatTag, NBT\IntTag, NBT\ListTag, NBT\StringTag};
/** The first packet sent to the client after they've logged in. */
class JoinGamePacket extends Packet
{
	/**
	 * @var GMP $eid
	 */
	public $eid;
	/**
	 * @var int $gamemode
	 */
	public $gamemode = Gamemode::SURVIVAL;
	/**
	 * @var bool $hardcore
	 */
	public $hardcore = false;
	/**
	 * Note: This value is sent and read as overworld in 1.16.
	 *
	 * @var int $dimension
	 */
	public $dimension = Dimension::OVERWORLD;
	/**
	 * @var int $render_distance
	 */
	public $render_distance = 8;
	/**
	 * Set to false when the doImmediateRespawn gamerule is true.
	 * Only for 1.15+ clients.
	 *
	 * @since 0.5.1
	 * @var bool $enable_respawn_screen
	 */
	public $enable_respawn_screen = true;

	/**
	 * @param GMP|int|string $eid
	 */
	function __construct($eid = 0)
	{
		if(!$eid instanceof GMP)
		{
			$eid = gmp_init($eid);
		}
		$this->eid = $eid;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return JoinGamePacket
	 * @throws IOException
	 */
	static function read(Connection $con): JoinGamePacket
	{
		$packet = new JoinGamePacket($con->readInt());
		$packet->gamemode = $con->readUnsignedByte();
		if($packet->gamemode >= 0x8)
		{
			$packet->gamemode -= 0x8;
			$packet->hardcore = true;
		}
		if($con->protocol_version >= 701)
		{
			$con->readUnsignedByte(); // Previous Gamemode
			$worlds = $con->readVarInt(); // World Count
			for($i = 0; $i < $worlds; $i++)
			{
				$con->ignoreBytes($con->readVarInt()); // World Name
			}
			$con->readNBT(); // Dimension Codec
			$con->ignoreBytes($con->readVarInt()); // Dimension
		}
		else
		{
			$packet->dimension = $con->protocol_version > 107 ? gmp_intval($con->readInt()) : $con->readByte();
		}
		if($con->protocol_version >= 565)
		{
			$con->ignoreBytes(8); // Hashed Seed (Long)
		}
		else if($con->protocol_version < 472)
		{
			$con->ignoreBytes(1); // Difficulty (Byte)
		}
		$con->ignoreBytes(1); // Max Players (Byte)
		if($con->protocol_version < 701)
		{
			$con->ignoreBytes(gmp_intval($con->readVarInt())); // Level Type (String)
		}
		if($con->protocol_version >= 472)
		{
			$packet->render_distance = gmp_intval($con->readVarInt()); // Render Distance
		}
		$con->ignoreBytes(1); // Reduced Debug Info (Boolean)
		if($con->protocol_version >= 565)
		{
			$packet->enable_respawn_screen = $con->readBoolean();
			if($con->protocol_version >= 701)
			{
				$con->ignoreBytes(2); // Is Debug & Is Flat
			}
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
		$con->startPacket("join_game");
		$con->writeInt($this->eid);
		$gamemode = $this->gamemode;
		if($this->hardcore)
		{
			$gamemode += 0x8;
		}
		$con->writeUnsignedByte($gamemode);
		if($con->protocol_version >= 701)
		{
			$con->writeUnsignedByte($gamemode);
			$con->writeVarInt(1); // World Count
			$con->writeString("world"); // World Names
			(new CompoundTag(""))->addChild(
				(new ListTag("dimension", CompoundTag::ORD, [
					(new CompoundTag(""))
						->addChild(new StringTag("name", "minecraft:overworld"))
						->addChild(new ByteTag("natural", 1))
						->addChild(new FloatTag("ambient_light", 1.0))
						->addChild(new ByteTag("has_ceiling", 0))
						->addChild(new ByteTag("has_skylight", 1))
						->addChild(new ByteTag("fixed_time", 1))
						->addChild(new ByteTag("shrunk", 0))
						->addChild(new ByteTag("ultrawarm", 0))
						->addChild(new ByteTag("has_raids", 1))
						->addChild(new ByteTag("respawn_anchor_works", 1))
						->addChild(new ByteTag("bed_works", 1))
						->addChild(new ByteTag("piglin_safe", 1))
						->addChild(new IntTag("logical_height", 256))
						->addChild(new StringTag("infiniburn", ""))
				])))->write($con);
			$con->writeString("minecraft:overworld");
		}
		else if($con->protocol_version >= 108)
		{
			$con->writeInt($this->dimension);
		}
		else
		{
			$con->writeByte($this->dimension);
		}
		if($con->protocol_version >= 701)
		{
			$con->writeString("world");
		}
		if($con->protocol_version >= 565)
		{
			$con->writeLong(0); // Hashed Seed
		}
		else if($con->protocol_version < 472)
		{
			$con->writeUnsignedByte(Difficulty::PEACEFUL);
		}
		$con->writeByte(100); // Max Players
		if($con->protocol_version < 701)
		{
			$con->writeString(""); // Level Type
		}
		if($con->protocol_version >= 472)
		{
			$con->writeVarInt($this->render_distance); // Render Distance
		}
		$con->writeBoolean(false); // Reduced Debug Info
		if($con->protocol_version >= 565)
		{
			$con->writeBoolean($this->enable_respawn_screen);
			if($con->protocol_version >= 701)
			{
				$con->writeBoolean(false); // Is Debug
				$con->writeBoolean(false); // Is Flat
			}
		}
		$con->send();
	}

	function __toString()
	{
		return "{JoinGamePacket: Entity ID ".gmp_strval($this->eid).", Gamemode ".(Gamemode::nameOf($this->gamemode) ?? $this->gamemode).", ".($this->hardcore ? "Not " : "")."Hardcore Mode, Dimension ".(Dimension::nameOf($this->dimension) ?? $this->dimension)."}";
	}
}
