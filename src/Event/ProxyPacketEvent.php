<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, PacketId, ServerConnection};
class ProxyPacketEvent extends ProxyEvent
{
	/**
	 * @var PacketId $packetId
	 */
	public $packetId;

	/**
	 * @param ClientConnection $client The client connection.
	 * @param ServerConnection|null $server The server connection.
	 * @param PacketId $packetId The ID of the packet being sent.
	 */
	function __construct(ClientConnection $client, $server, PacketId $packetId)
	{
		parent::__construct($client, $server);
		$this->packetId = $packetId;
	}
}
