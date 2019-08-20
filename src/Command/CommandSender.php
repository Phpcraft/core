<?php
namespace Phpcraft\Command;
interface CommandSender
{
	/**
	 * @param array|string $message
	 */
	function sendMessage($message);
}