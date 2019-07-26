<?php
namespace Phpcraft\Packet;
class ServerboundBrandPluginMessagePacket extends ServerboundStringPluginMessagePacket
{
	/**
	 * @param string $data The brand.
	 */
	function __construct(string $data = "")
	{
		parent::__construct(self::CHANNEL_BRAND, $data);
	}
}
