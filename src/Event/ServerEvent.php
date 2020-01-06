<?php
namespace Phpcraft\Event;
use hotswapp\Event;
use Phpcraft\Server;
abstract class ServerEvent extends Event
{
	/**
	 * The server emitting the event.
	 *
	 * @var Server $server
	 */
	public $server;

	function __construct(Server $server)
	{
		$this->server = $server;
	}
}
