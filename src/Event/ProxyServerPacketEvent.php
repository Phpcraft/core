<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ServerboundPacketId, ServerConnection};
/**
 * The event emitted by the proxy when one of its clients sends a packet to their server. Cancellable.
 * Cancelling the event prevents the recipient from receiving the packet.
 */
class ProxyServerPacketEvent extends ProxyPacketEvent
{
	/**
	 * @param ClientConnection $client The client connection.
	 * @param ServerConnection|null $server The server connection.
	 * @param ServerboundPacketId $packetId The ID of the packet the client has sent.
	 */
	function __construct(ClientConnection $client, $server, ServerboundPacketId $packetId)
	{
		parent::__construct($client, $server, $packetId);
	}
}
