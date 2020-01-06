<?php
namespace Phpcraft\Event;
use hotswapp\Event;
use Phpcraft\
{ProxyServer, Server};
class ProxyEvent extends Event
{
	/**
	 * The proxy server.
	 *
	 * @var Server $server
	 */
	public $server;

	function __construct(ProxyServer $server)
	{
		$this->server = $server;
	}
}
