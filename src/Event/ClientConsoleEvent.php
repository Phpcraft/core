<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\ServerConnection;
/** The event emitted by the client when the console has proposed a message. Cancellable. */
class ClientConsoleEvent extends ClientEvent
{
	use CancellableEvent;

	/**
	 * The message that the console has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	function __construct(ServerConnection $server, string $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
