<?php
namespace Phpcraft;
abstract class ClientEvent extends Event
{
	/**
	 * The client's server connection.
	 * @var ServerConnection $server
	 */
	public $server;

	/**
	 * @param ServerConnection $server The client's server connection.
	 */
	public function __construct(ServerConnection $server)
	{
		$this->server = $server;
	}
}
