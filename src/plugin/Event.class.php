<?php
namespace Phpcraft;
abstract class Event
{
	const PRIORITY_HIGHEST = 2;
	const PRIORITY_HIGH = 1;
	const PRIORITY_NORMAL = 0;
	const PRIORITY_LOW = -1;
	const PRIORITY_LOWEST = -2;
	/**
	 * Wether or not the event was cancelled.
	 * Note that, depending on the plugin platform and event, this value might not have an effect.
	 *
	 * @var boolean $cancelled
	 */
	public $cancelled = false;
}
