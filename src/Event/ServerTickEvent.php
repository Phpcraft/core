<?php
namespace Phpcraft\Event;
use Phpcraft\Server;
class ServerTickEvent extends ServerEvent
{
	/**
	 * True if this tick event should've been fired much earlier but wasn't because the server was busy. If your task is complex and/or doesn't need to be executed every tick, try not doing it if this is true.
	 *
	 * @var bool $lagging
	 */
	public $lagging;

	function __construct(Server $server, bool $lagging)
	{
		parent::__construct($server);
		$this->lagging = $lagging;
	}
}
