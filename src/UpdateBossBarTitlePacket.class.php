<?php
namespace Phpcraft;
class UpdateBossBarTitlePacket extends BossBarPacket
{
	/**
	 * The "title" of the boss bar; chat object.
	 * @var array $title
	 */
	public $title = ["text" => ""];

	/**
	 * @copydoc BossBarPacket::__construct
	 * @param string $title The "title" of the boss bar; chat object.
	 */
	function __construct($uuid = null, $title = ["text" => ""])
	{
		$this->uuid = $uuid;
		$this->title = $title;
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(\Phpcraft\Connection $con)
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
			$con->writeVarInt($this->uuid->toInt() * -1);
			$metadata = new \Phpcraft\EntityLiving();
			$metadata->custom_name = $this->title;
			$metadata->write($con);
		}
		$con->send();
	}

	function toString()
	{
		return "{UpdateBossBarTitlePacket: Boss Bar ".$this->uuid->toString().", \"".\Phpcraft\Phpcraft::chatToText($this->title)."\"}";
	}
}
