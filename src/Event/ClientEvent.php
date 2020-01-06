<?php
namespace Phpcraft\Event;
use hotswapp\Event;
use Phpcraft\ServerConnection;
abstract class ClientEvent extends Event
{
	/**
	 * The client's server connection.
	 *
	 * @var ServerConnection $server
	 */
	public $server;

	function __construct(ServerConnection $server)
	{
		$this->server = $server;
	}
}
