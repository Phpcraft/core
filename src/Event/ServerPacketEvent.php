<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ServerboundPacket, Server};
/**
 * The event emitted by the server when a client has sent a packet. Cancellable.
 * Cancelling the event tells the server to ignore the packet.
 */
class ServerPacketEvent extends ServerClientEvent
{
	/**
	 * The ID of the packet that the client has sent.
	 *
	 * @var ServerboundPacket $packetId
	 */
	public $packetId;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 * @param ServerboundPacket $packetId The ID of the packet that the client has sent.
	 */
	function __construct(Server $server, ClientConnection $client, ServerboundPacket $packetId)
	{
		parent::__construct($server, $client);
		$this->packetId = $packetId;
	}
}
