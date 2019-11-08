<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Entity\Living, Server};
/** Fired by the server when a client changes their metadata (starting or stopping to crouch or sprint.) */
class ServerClientMetadataEvent extends ServerClientEvent
{
	/**
	 * The client's metadata prior to this event.
	 *
	 * @var Living $prev_metadata
	 */
	public $prev_metadata;

	function __construct(Server $server, ClientConnection $client, Living $prev_metadata)
	{
		parent::__construct($server, $client);
		$this->prev_metadata = $prev_metadata;
	}
}
