<?php
namespace Phpcraft\Packet;
use hellsh\UUID;
use Phpcraft\
{Connection, EntityLiving, Exception\IOException, Phpcraft};
class UpdateBossBarTitlePacket extends BossBarPacket
{
	/**
	 * The "title" of the boss bar; chat object.
	 *
	 * @var array $title
	 */
	public $title = ["text" => ""];

	/**
	 * @param UUID $uuid The UUID of the boss bar.
	 * @param array|string $title The "title" of the boss bar; chat object.
	 */
	function __construct(UUID $uuid = null, $title = ["text" => ""])
	{
		parent::__construct($uuid);
		$this->title = $title;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		if($con->protocol_version > 49)
		{
			$con->startPacket("boss_bar");
			$con->writeUuid($this->uuid);
			$con->writeVarInt(3);
			$con->writeChat($this->title);
		}
		else
		{
			$con->startPacket("entity_metadata");
			/** @noinspection PhpUndefinedMethodInspection */
			$con->writeVarInt($this->uuid->toInt() * -1);
			$metadata = new EntityLiving();
			$metadata->custom_name = $this->title;
			$metadata->write($con);
		}
		$con->send();
	}

	function __toString()
	{
		return "{UpdateBossBarTitlePacket: Boss Bar ".$this->uuid->__toString().", \"".Phpcraft::chatToText($this->title)."\"}";
	}
}
