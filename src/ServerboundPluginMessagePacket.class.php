<?php
namespace Phpcraft;
class ServerboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	public function __construct($channel = "", $data = "")
	{
		parent::__construct("serverbound_plugin_message", $channel, $data);
	}

	/**
	 * @copydoc Packet::read
	 */
	public static function read(Connection $con)
	{
		return self::_read($con, new ClientboundPluginMessagePacket());
	}

	public function toString()
	{
		return "{ServerboundPluginMessagePacket: \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
