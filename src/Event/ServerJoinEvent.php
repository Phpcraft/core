<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
/**
 * The event emitted by the server when a client has joined it. Cancellable.
 * If you cancel this event, please also call ClientConnection::disconnect so the client knows why they can't join.
 */
class ServerJoinEvent extends ServerClientEvent
{
	use CancellableEvent;
}
