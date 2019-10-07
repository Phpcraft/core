<?php /** @noinspection PhpUnused */
namespace Phpcraft\Packet\BossBar;
use hellsh\UUID;
use Phpcraft\
{Connection, Exception\IOException, Packet\Packet};
abstract class BossBarPacket extends Packet
{
	const COLOR_PINK = 0;
	const COLOR_BLUE = 1;
	const COLOR_RED = 2;
	const COLOR_GREEN = 3;
	const COLOR_YELLOW = 4;
	const COLOR_PURPLE = 5;
	const COLOR_WHITE = 6;
	const DIVISION_0 = 0;
	const DIVISION_6 = 1;
	const DIVISION_10 = 2;
	const DIVISION_12 = 3;
	const DIVISION_20 = 4;
	/**
	 * The UUID of the boss bar.
	 *
	 * @var UUID $uuid
	 */
	public $uuid;

	/**
	 * @param UUID $uuid The UUID of the boss bar.
	 */
	function __construct(UUID $uuid = null)
	{
		$this->uuid = $uuid;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return BossBarPacket|null
	 * @throws IOException
	 * @todo Implement every subpacket.
	 */
	static function read(Connection $con)
	{
		$uuid = $con->readUuid();
		$action = gmp_intval($con->readVarInt());
		switch($action)
		{
			case 0:
				$packet = new AddBossBarPacket($uuid);
				$packet->title = $con->readChat();
				$packet->health = $con->readFloat();
				$packet->color = gmp_intval($con->readVarInt());
				$packet->division = gmp_intval($con->readVarInt());
				$flags = $con->readUnsignedByte();
				if($flags & 0x04)
				{
					$packet->create_fog = true;
				}
				if($flags & 0x02)
				{
					if($con->protocol_version < 395)
					{
						$packet->create_fog = true;
					}
					$packet->play_end_music = true;
				}
				if($flags & 0x01)
				{
					$packet->darken_sky = true;
				}
				return $packet;
			case 1:
				return new RemoveBossBarPacket($uuid);
			case 2:
				return new UpdateBossBarHealthPacket($uuid, $con->readFloat());
			case 3:
				return new UpdateBossBarTitlePacket($uuid, $con->readChat());
			default:
				trigger_error("Unimplemented boss bar action: ".$action);
		}
		return null;
	}
}
