<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, ServerConnection};
class ProxyEvent extends Event
{
	/**
	 * The proxy connection.
	 *
	 * @var ClientConnection|null $client
	 */
	public $client;
	/**
	 * The server connection.
	 *
	 * @var ServerConnection|null $server
	 */
	public $server;

	/**
	 * @param ClientConnection|null $client The client connection.
	 * @param ServerConnection|null $server The server connection.
	 */
	function __construct($client, $server)
	{
		$this->client = $client;
		$this->server = $server;
	}
}
