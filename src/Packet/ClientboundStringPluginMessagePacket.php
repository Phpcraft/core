<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
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

	/**
	 * @param Connection $con
	 * @throws IOException
	 */
	protected function read_(Connection $con)
	{
		$this->data = $con->readString();
	}

	protected function send_(Connection $con)
	{
		$con->writeString($this->data);
	}
}