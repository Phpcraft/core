<?php
namespace Phpcraft;
class ServerboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	public function __construct(string $channel = "", string $data = "")
	{
		parent::__construct("serverbound_plugin_message", $channel, $data);
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return ServerboundPluginMessagePacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		$packet = new ServerboundPluginMessagePacket();
		$packet->_read($con);
		return $packet;
	}

	public function __toString()
	{
		return "{ServerboundPluginMessagePacket: \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
