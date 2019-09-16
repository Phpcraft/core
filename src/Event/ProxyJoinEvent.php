<?php
namespace Phpcraft\Event;
/**
 * The event emitted by the proxy when a client has entered state 3 (playing). Cancellable.
 * If you cancel this event, please also call ClientConnection::disconnect so the client knows why they can't join.
 */
class ProxyJoinEvent extends ProxyEvent
{
}
