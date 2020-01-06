<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\Server;
/**
 * The event emitted by the server when the console has proposed a broadcast. Cancellable.
 * Cancelling the event prevents the broadcast.
 */
class ServerConsoleEvent extends ServerEvent
{
	use CancellableEvent;

	/**
	 * The message that the console has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	function __construct(Server $server, string $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
