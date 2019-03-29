<?php
namespace Phpcraft;
class ClientboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	public function __construct($channel = "", $data = "")
	{
		parent::__construct("clientbound_plugin_message", $channel, $data);
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return ClientboundPluginMessagePacket
	 * @throws Exception
	 */
	public static function read(Connection $con)
	{
		$packet = new ClientboundPluginMessagePacket();
		$packet->_read($con);
		return $packet;
	}

	public function __toString()
	{
		return "{ClientboundPluginMessagePacket: \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
