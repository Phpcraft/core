<?php
namespace Phpcraft\Packet\PluginMessage;
use Phpcraft\Connection;
use Phpcraft\Exception\IOException;
use Phpcraft\Packet\Packet;
class ServerboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	function __construct(string $channel = "", string $data = "")
	{
		parent::__construct("serverbound_plugin_message", $channel, $data);
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return Packet
	 * @throws IOException
	 */
	static function read(Connection $con): Packet
	{
		$channel = self::readChannel($con);
		switch($channel)
		{
			case "minecraft:brand":
				return new ServerboundBrandPluginMessagePacket($con->readString());
		}
		$packet = new ServerboundPluginMessagePacket($channel, $con->read_buffer);
		$con->read_buffer = "";
		return $packet;
	}
}
