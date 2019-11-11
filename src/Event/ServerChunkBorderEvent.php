<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Point3D, Server};
/** Fired when a client crosses a chunk border. */
class ServerChunkBorderEvent extends ServerMovementEvent
{
	/**
	 * The client's chunk x position before this event.
	 *
	 * @var int|null $prev_chunk_x
	 */
	public $prev_chunk_x;
	/**
	 * The client's chunk z position before this event.
	 *
	 * @var int|null $prev_chunk_z
	 */
	public $prev_chunk_z;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param Point3D|null $prev_pos
	 * @param int|null $prev_chunk_x
	 * @param int|null $prev_chunk_z
	 */
	function __construct(Server $server, ClientConnection $client, ?Point3D $prev_pos = null, $prev_chunk_x = null, $prev_chunk_z = null)
	{
		parent::__construct($server, $client, $prev_pos);
		$this->prev_chunk_x = $prev_chunk_x;
		$this->prev_chunk_z = $prev_chunk_z;
	}
}
