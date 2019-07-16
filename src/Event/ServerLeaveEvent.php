<?php
namespace Phpcraft\Event;
/**
 * The event emitted when a client who had reached state 3 disconnects.
 * Can be cancelled to prevent the "username left the game" message.
 */
class ServerLeaveEvent extends ServerClientEvent
{
}
