<?php
namespace Phpcraft\Command;
use Phpcraft\
{ChatComponent, Point3D, Server};
interface CommandSender
{
	/**
	 * @return string
	 */
	function getName(): string;

	/**
	 * @param array|string|null|ChatComponent $message
	 * @return void
	 */
	function sendMessage($message): void;

	/**
	 * @param array|string|null|ChatComponent $message
	 * @param string $permission
	 * @return void
	 */
	function sendAdminBroadcast($message, string $permission = "everything"): void;

	/**
	 * @param string $permission
	 * @return bool
	 */
	function hasPermission(string $permission): bool;

	/**
	 * @return bool
	 */
	function hasPosition(): bool;

	/**
	 * @return Point3D|null
	 */
	function getPosition(): ?Point3D;

	/**
	 * @return bool
	 */
	function hasServer(): bool;

	/**
	 * @return Server|null
	 */
	function getServer(): ?Server;
}
