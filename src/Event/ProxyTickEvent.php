<?php
namespace Phpcraft\Event;
use Phpcraft\ProxyServer;
class ProxyTickEvent extends ProxyEvent
{
	/**
	 * True if this tick event should've been fired much earlier but wasn't because the proxy server was busy. If your task is complicated and/or doesn't need to be executed every tick, try not doing it if this is true.
	 *
	 * @var bool $lagging
	 */
	public $lagging;

	function __construct(ProxyServer $server, bool $lagging)
	{
		parent::__construct($server);
		$this->lagging = $lagging;
	}
}
