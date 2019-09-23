<?php
namespace Phpcraft\Event;
use Phpcraft\
{ClientConnection, Packet\ClientSettingsPacket, Server};
/** Fired by the server when a client announces their settings after joining or when they have been changed. At this point, the values from the packet have not yet been copied into the ClientConnection. */
class ServerClientSettingsEvent extends ServerClientEvent
{
	/**
	 * @var ClientSettingsPacket $packet
	 */
	public $packet;

	function __construct(Server $server, ClientConnection $client, ClientSettingsPacket $packet)
	{
		parent::__construct($server, $client);
		$this->packet = $packet;
	}
}
