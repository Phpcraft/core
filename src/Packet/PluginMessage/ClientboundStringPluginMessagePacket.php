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
		$str = "{".substr(get_called_class(), 30);
		if(get_called_class() == __CLASS__)
		{
			$str .= ": \"{$this->channel}\"";
		}
		return $str.": ".$this->data."}";
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	protected function send_(Connection $con): void
	{
		$con->writeString($this->data);
	}
}
