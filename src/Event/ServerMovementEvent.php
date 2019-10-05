<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Point3D, Server};
/** Fired when a client moves. Canceling puts the client back to where they were before this event. */
class ServerMovementEvent extends ServerClientEvent
{
	/**
	 * The client's position before this event.
	 *
	 * @var Point3D|null $prev_pos
	 */
	public $prev_pos;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param Point3D|null $prev_pos
	 */
	function __construct(Server $server, ClientConnection $client, $prev_pos = null)
	{
		parent::__construct($server, $client);
		$this->prev_pos = $prev_pos;
	}
}
