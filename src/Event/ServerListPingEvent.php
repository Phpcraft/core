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
class ServerListPingEvent extends ServerClientEvent
{
	use CancellableEvent;
	/**
	 * The list ping data that will be responded with.
	 *
	 * @var array $data
	 */
	public $data;

	function __construct(Server $server, ClientConnection $client, array &$data)
	{
		parent::__construct($server, $client);
		$this->data = $data;
	}
}
