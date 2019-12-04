<?php
namespace Phpcraft\Packet\BossBar;
use hellsh\UUID;
use Phpcraft\
{ChatComponent, Connection, Entity\Living, Exception\IOException};
class UpdateBossBarTitlePacket extends BossBarPacket
{
	/**
	 * The "title" of the boss bar.
	 *
	 * @var ChatComponent $title
	 */
	public $title;

	/**
	 * @param UUID $uuid The UUID of the boss bar.
	 * @param array|string|null|ChatComponent $title The "title" of the boss bar.
	 */
	function __construct(UUID $uuid, $title)
	{
		parent::__construct($uuid);
		$this->title = ChatComponent::cast($title);
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
			$con->writeVarInt(abs($this->uuid->hashCode()) * -1);
			$metadata = new Living();
			$metadata->custom_name = $this->title;
			$metadata->write($con);
		}
		$con->send();
	}

	function __toString()
	{
		return "{UpdateBossBarTitlePacket: Boss Bar {$this->uuid}, \"".$this->title->toString()."\"}";
	}
}
