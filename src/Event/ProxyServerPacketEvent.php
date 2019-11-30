<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ServerboundPacketId, ProxyServer};
/**
 * The event emitted by the proxy when one of its clients sends a packet to their server. Cancellable.
 * Cancelling the event prevents the recipient from receiving the packet.
 */
class ProxyServerPacketEvent extends ProxyPacketEvent
{
	/**
	 * @var ServerboundPacketId $packetId
	 */
	public $packetId;

	function __construct(ProxyServer $server, ClientConnection $client, ServerboundPacketId $packetId)
	{
		parent::__construct($server, $client, $packetId);
	}
}
