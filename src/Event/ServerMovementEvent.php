<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\
{ClientConnection, Point3D, Server};
/**
 * Fired when a client moves. Cancellable.
 * Cancelling puts the client back to where they were before this event.
 */
class ServerMovementEvent extends ServerClientEvent
{
	use CancellableEvent;
	/**
	 * The client's position before this event.
	 *
	 * @var Point3D|null $prev_pos
	 */
	public $prev_pos;

	function __construct(Server $server, ClientConnection $client, ?Point3D $prev_pos = null)
	{
		parent::__construct($server, $client);
		$this->prev_pos = $prev_pos;
	}
}
