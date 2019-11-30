<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, ProxyServer};
class ProxyClientEvent extends ProxyEvent
{
	/**
	 * @var ClientConnection $client
	 */
	public $client;

	function __construct(ProxyServer $server, ClientConnection $client)
	{
		parent::__construct($server);
		$this->client = $client;
	}
}
