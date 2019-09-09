<?php
namespace Phpcraft\Event;
use Phpcraft\
{Packet\ClientboundPacket, ServerConnection};
/**
 * The event emitted by the client when the server has sent a packet. Cancellable.
 * Cancelling the event tells the client to ignore the packet.
 */
class ClientPacketEvent extends ClientEvent
{
	/**
	 * The ID of the packet that the server has sent.
	 *
	 * @var ClientboundPacket $packetId
	 */
	public $packetId;

	/**
	 * @param ServerConnection $server The client's server connection.
	 * @param ClientboundPacket $packetId The ID of the packet that the server has sent.
	 */
	function __construct(ServerConnection $server, ClientboundPacket $packetId)
	{
		parent::__construct($server);
		$this->packetId = $packetId;
	}
}
