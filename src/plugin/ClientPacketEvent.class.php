<?php
namespace Phpcraft;
/**
 * The event emitted by the client when the server has sent a packet. Cancellable.
 * Cancelling the event tells the client to ignore the packet.
 */
class ClientPacketEvent extends ClientEvent
{
	/**
	 * The name of the packet that the server has sent.
	 * @var string $packet_name
	 */
	public $packet_name;

	/**
	 * @param ServerConnection $server The client's server connection.
	 * @param string $packet_name The name of the packet that the server has sent.
	 */
	public function __construct(ServerConnection $server, $packet_name)
	{
		parent::__construct($server);
		$this->packet_name = $packet_name;
	}
}
