<?php
namespace Phpcraft;
/**
 * The event emitted by the server when a client has sent a packet. Cancellable.
 * Cancelling the event tells the server to ignore the packet.
 */
class ServerPacketEvent extends ServerClientEvent
{
	/**
	 * The name of the packet that the client has sent.
	 *
	 * @var string $packet_name
	 */
	public $packet_name;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param string $packet_name The name of the packet that the client has sent.
	 */
	public function __construct(Server $server, ClientConnection $client, string $packet_name)
	{
		parent::__construct($server, $client);
		$this->packet_name = $packet_name;
	}
}
