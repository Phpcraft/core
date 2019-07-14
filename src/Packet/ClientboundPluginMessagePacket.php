<?php
namespace Phpcraft\Packet;
class ClientboundPluginMessagePacket extends PluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	public function __construct(string $channel = "", string $data = "")
	{
		parent::__construct("clientbound_plugin_message", $channel, $data);
	}
}
