<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ClientboundPacketId, ProxyServer};
/**
 * The event emitted by the proxy when it receives a packet addressed to one of its clients. Cancellable.
 * Cancelling the event prevents the recipient from receiving the packet.
 */
class ProxyClientPacketEvent extends ProxyPacketEvent
{
	/**
	 * @var ClientboundPacketId $packetId
	 */
	public $packetId;

	function __construct(ProxyServer $server, ClientConnection $client, ClientboundPacketId $packetId)
	{
		parent::__construct($server, $client, $packetId);
	}
}
