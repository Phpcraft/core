<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Server};
use hotswapp\CancellableEvent;
/** The event emitted by the server when a client has proposed a chat message. Cancellable. */
class ServerChatEvent extends ServerClientEvent
{
	use CancellableEvent;

	/**
	 * The message that the client has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	function __construct(Server $server, ClientConnection $client, string $message)
	{
		parent::__construct($server, $client);
		$this->message = $message;
	}
}
