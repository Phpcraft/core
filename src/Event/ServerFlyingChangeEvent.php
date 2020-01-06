<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Server};
/**
 * The event emitted by the server when a client's flying value has changed.
 *
 * @see ClientConnection::flying
 */
class ServerFlyingChangeEvent extends ServerClientEvent
{
	/**
	 * The client's flying value before the change.
	 *
	 * @var boolean $old_value
	 */
	public $old_value;

	function __construct(Server $server, ClientConnection $client, bool $old_value)
	{
		parent::__construct($server, $client);
		$this->old_value = $old_value;
	}
}
