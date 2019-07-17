<?php
namespace Phpcraft\Event;
use Phpcraft\ServerConnection;
abstract class ClientEvent extends Event
{
	/**
	 * The client's server connection.
	 *
	 * @var ServerConnection $server
	 */
	public $server;

	/**
	 * @param ServerConnection $server The client's server connection.
	 */
	function __construct(ServerConnection $server)
	{
		$this->server = $server;
	}
}
