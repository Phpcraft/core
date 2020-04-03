<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\
{ClientConnection, Server};
/**
 * The event emitted by the server when it has been list pinged. Cancellable.
 *
 * @since 0.5.20
 */
class ServerListPingEvent extends ServerEvent
{
	use CancellableEvent;
	/**
	 * The client that has triggered this event or null if called internally to get list ping information.
	 *
	 * @var ClientConnection|null $client
	 */
	public $client;
	/**
	 * The list ping data that will be responded with.
	 *
	 * @var array $data
	 */
	public $data;

	function __construct(Server $server, ?ClientConnection $client, array &$data)
	{
		parent::__construct($server);
		$this->client = $client;
		$this->data = $data;
	}
}
