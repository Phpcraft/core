<?php
namespace Phpcraft;
abstract class ServerEvent extends Event
{
	/**
	 * The server emitting the event.
	 * @var Server $server
	 */
	public $server;

	/**
	 * @param Server $server
	 */
	public function __construct(Server $server)
	{
		$this->server = $server;
	}
}
