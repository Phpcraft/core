<?php
namespace Phpcraft\Packet;
use Phpcraft\World\Structure;
/**
 * @since 0.5
 */
interface WorldChangingPacket
{
	function toStructure(): Structure;
}
