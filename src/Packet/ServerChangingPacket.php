<?php
namespace Phpcraft\Packet;
use Phpcraft\BareServer;
/**
 * @since 0.5.5
 */
interface ServerChangingPacket
{
	function apply(BareServer $server): void;
}
