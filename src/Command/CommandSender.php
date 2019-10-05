<?php
namespace Phpcraft\Command;
use Phpcraft\
{Point3D, Server};
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
	 * @return Point3D|null
	 */
	function getPosition();
}
