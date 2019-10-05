<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Server};
/** Fired when a client rotates. Canceling rotates the client the way they were before this event. */
class ServerRotationEvent extends ServerClientEvent
{
	/**
	 * The client's yaw before this event.
	 *
	 * @var int|null $prev_yaw
	 */
	public $prev_yaw;
	/**
	 * The client's pitch before this event.
	 *
	 * @var int|null $prev_pitch
	 */
	public $prev_pitch;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param int|null $prev_yaw
	 * @param int|null $prev_pitch
	 */
	function __construct(Server $server, ClientConnection $client, $prev_yaw = null, $prev_pitch = null)
	{
		parent::__construct($server, $client);
		$this->prev_yaw = $prev_yaw;
		$this->prev_pitch = $prev_pitch;
	}
}
