<?php
namespace Phpcraft\Command;
use Phpcraft\Point3D;
interface CommandSender
{
	function getName(): string;

	/**
	 * @param array|string $message
	 */
	function sendMessage($message);

	/**
	 * @param array|string $message
	 * @param string $permission
	 */
	function sendAdminBroadcast($message, string $permission = "everything");

	function hasPermission(string $permission): bool;

	function hasPosition(): bool;

	/**
	 * @return Point3D|null
	 */
	function getPosition();
}
