<?php
namespace Phpcraft\Packet\PluginMessage;
use Phpcraft\Connection;
/** A clientbound plugin message with only a string as data. */
class ClientboundStringPluginMessagePacket extends ClientboundPluginMessagePacket
{
	/**
	 * @param string $channel The name of the plugin message's channel.
	 * @param string $data The data of the plugin message.
	 */
	function __construct(string $channel = "", string $data = "")
	{
		parent::__construct($channel, $data);
	}

	function __toString()
	{
		return "{".substr(get_called_class(), 30).": \"{$this->channel}\": {$this->data}}";
	}

	protected function send_(Connection $con): void
	{
		$con->writeString($this->data);
	}
}
