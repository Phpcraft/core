<?php
namespace Phpcraft\Event;
use Phpcraft\
{Packet\ClientboundPacketId, ServerConnection};
/**
 * The event emitted by the client when the server has sent a packet. Cancellable.
 * Cancelling the event tells the client to ignore the packet.
 */
class ClientPacketEvent extends ClientEvent
{
	/**
	 * The ID of the packet that the server has sent.
	 *
	 * @var ClientboundPacketId $packetId
	 */
	public $packetId;

	/**
	 * @param ServerConnection $server The client's server connection.
	 * @param ClientboundPacketId $packetId The ID of the packet that the server has sent.
	 */
	function __construct(ServerConnection $server, ClientboundPacketId $packetId)
	{
		parent::__construct($server);
		$this->packetId = $packetId;
	}
}
