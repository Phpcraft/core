<?php
namespace Phpcraft;
/** The event emitted by the server when a client has proposed a chat message. Cancellable. */
class ServerChatEvent extends ServerClientEvent
{
	/**
	 * The message that the client has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param string $message The message that the client has proposed.
	 */
	public function __construct(Server $server, ClientConnection $client, string $message)
	{
		parent::__construct($server, $client);
		$this->message = $message;
	}
}
