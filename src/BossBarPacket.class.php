<?php
namespace Phpcraft;
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
	 * @var UUID $uuid
	 */
	public $uuid;

	/**
	 * The constructor.
	 * @param UUID $uuid The UUID of the boss bar.
	 */
	public function __construct($uuid = null)
	{
		$this->uuid = $uuid;
	}

	/**
	 * @copydoc Packet::read
	 */
	public static function read(Connection $con)
	{
		$uuid = $con->readUuid();
		$action = $con->readVarInt();
		switch($action)
		{
			case 0:
			$packet = new AddBossBarPacket($uuid);
			$packet->title = $con->readChat();
			$packet->health = $con->readFloat();
			$packet->color = $con->readVarInt();
			$packet->division = $con->readVarInt();
			$flags = $con->readByte();
			if($flags >= 0x4)
			{
				$packet->create_fog = true;
				$flags -= 0x4;
			}
			if($flags >= 0x2)
			{
				if($con->protocol_version < 395)
				{
					$packet->create_fog = true;
				}
				$packet->play_end_music = true;
				$flags -= 0x2;
			}
			if($flags >= 0x1)
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
