<?php
namespace Phpcraft\Command;
use Phpcraft\
{Position, Server};
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

	function hasPosition(): bool;

	/**
	 * @return Position|null
	 */
	function getPosition();
}
