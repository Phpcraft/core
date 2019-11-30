<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, ProxyServer};
/**
 * The event emitted by the proxy when it has connected a client to a downstream server.
 */
class ProxyConnectEvent extends ProxyClientEvent
{
	/**
	 * @var string $address
	 */
	public $address;

	function __construct(ProxyServer $server, ClientConnection $client, string $address)
	{
		parent::__construct($server, $client);
		$this->address = $address;
	}
}
