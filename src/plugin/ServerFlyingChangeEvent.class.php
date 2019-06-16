<?php
namespace Phpcraft;
/**
 * The event emitted by the server when a client's flying value has changed. Not cancellable.
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

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param boolean $old_value The client's flying value before the change.
	 */
	public function __construct(Server $server, ClientConnection $client, bool $old_value)
	{
		parent::__construct($server, $client);
		$this->old_value = $old_value;
	}
}
