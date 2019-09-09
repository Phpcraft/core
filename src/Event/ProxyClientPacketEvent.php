<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ClientboundPacket, ServerConnection};
/**
 * The event emitted by the proxy when it receives a packet addressed to one of its clients. Cancellable.
 * Cancelling the event prevents the recipient from receiving the packet.
 */
class ProxyClientPacketEvent extends ProxyPacketEvent
{
	/**
	 * @param ClientConnection $client The client connection.
	 * @param ServerConnection $server The server connection.
	 * @param ClientboundPacket $packetId The ID of the packet the server has sent.
	 */
	function __construct(ClientConnection $client, ServerConnection $server, ClientboundPacket $packetId)
	{
		parent::__construct($client, $server, $packetId);
	}
}
