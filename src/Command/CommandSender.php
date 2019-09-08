<?php
namespace Phpcraft\Command;
use Phpcraft\Server;
interface CommandSender
{
	/**
	 * @param array|string $message
	 */
	function sendMessage($message);

	function hasPermission(string $permission): bool;

	function hasServer(): bool;

	/**
	 * @return Server|null
	 */
	function getServer();
}
