<?php
namespace Phpcraft\Command;
use Phpcraft\
{Position, Server};
interface CommandSender
{
	function getName(): string;

	/**
	 * @param array|string $message
	 */
	function sendMessage($message);

	/**
	 * @param array|string $message
	 */
	function sendAndPrintMessage($message);

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
