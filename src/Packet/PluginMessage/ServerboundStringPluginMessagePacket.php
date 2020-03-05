<?php
namespace Phpcraft\Packet\PluginMessage;
use Phpcraft\Connection;
/** A serverbound plugin message with only a string as data. */
class ServerboundStringPluginMessagePacket extends ServerboundPluginMessagePacket
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
		$str = "{".substr(get_called_class(), 30);
		if(get_called_class() == __CLASS__)
		{
			$str .= ": \"{$this->channel}\"";
		}
		return $str.": ".$this->data."}";
	}

	protected function send_(Connection $con): void
	{
		$con->writeString($this->data);
	}
}
