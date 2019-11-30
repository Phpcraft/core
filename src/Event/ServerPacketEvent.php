<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ServerboundPacketId, Server};
/**
 * The event emitted by the server when a client has sent a packet. Cancellable.
 * Cancelling the event tells the server to ignore the packet.
 */
class ServerPacketEvent extends ServerClientEvent
{
	/**
	 * The ID of the packet that the client has sent.
	 *
	 * @var ServerboundPacketId $packetId
	 */
	public $packetId;

	function __construct(Server $server, ClientConnection $client, ServerboundPacketId $packetId)
	{
		parent::__construct($server, $client);
		$this->packetId = $packetId;
	}
}
