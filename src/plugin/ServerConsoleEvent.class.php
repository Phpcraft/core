<?php
namespace Phpcraft;
/** The event emitted by the server when the console has proposed a broadcast. Cancellable. */
class ServerConsoleEvent extends ServerEvent
{
	/**
	 * The message that the console has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	/**
	 * @param Server $server
	 * @param string $message The message that the console has proposed.
	 */
	public function __construct(Server $server, string $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
