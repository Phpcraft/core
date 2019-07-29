<?php
namespace Phpcraft\Enum;
use hellsh\Enum;
class ChatPosition extends Enum
{
	/**
	 * A message sent by another client, displayed in the chat box.
	 */
	const PLAYER = 0;
	/**
	 * A message generated by the server, displayed in the chat box.
	 */
	const SYSTEM = 1;
	const ABOVE_HOTBAR = 2;
}