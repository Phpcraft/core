<?php
namespace Phpcraft\Event;
use Phpcraft\ServerConnection;
/** The event emitted by the client when the console has proposed a message. Cancellable. */
class ClientConsoleEvent extends ClientEvent
{
	/**
	 * The message that the console has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	/**
	 * @param ServerConnection $server The client's server connection.
	 * @param string $message The message that the console has proposed.
	 */
	public function __construct(ServerConnection $server, string $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
