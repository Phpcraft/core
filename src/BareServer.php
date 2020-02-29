<?php
namespace Phpcraft;
use Phpcraft\World\World;
/**
 * The bare minimium of a server, featuring worlds and entity tracking.
 *
 * @since 0.5.5
 */
class BareServer
{
	/**
	 * An array of worlds that clients can be presented with.
	 *
	 * @var World[] $worlds
	 * @since 0.5.6
	 */
	public $worlds = [];
	/**
	 * The counter used to assign entity IDs.
	 *
	 * @var Counter $eidCounter
	 */
	public $eidCounter;
}
