<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\
{ClientConnection, Packet\PacketId, ProxyServer};
class ProxyPacketEvent extends ProxyClientEvent
{
	use CancellableEvent;
	/**
	 * @var PacketId $packetId
	 */
	public $packetId;

	function __construct(ProxyServer $server, ClientConnection $client, PacketId $packetId)
	{
		parent::__construct($server, $client);
		$this->packetId = $packetId;
	}
}
