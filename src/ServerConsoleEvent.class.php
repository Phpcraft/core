<?php
namespace Phpcraft;
/** The event emitted by the server when the console has proposed a broadcast. Cancellable. */
class ServerConsoleEvent extends ServerEvent
{
	/**
	 * The message that the console has proposed.
	 * @var string
	 */
	public $message;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param string $message The message that the console has proposed.
	 */
	public function __construct(Server $server, $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
