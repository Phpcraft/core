<?php
namespace Phpcraft;
class ServerboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	function __construct($channel = "", $data = "")
	{
		parent::__construct("serverbound_plugin_message", $channel, $data);
	}

	function toString()
	{
		return "{ServerboundPluginMessagePacket: \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
