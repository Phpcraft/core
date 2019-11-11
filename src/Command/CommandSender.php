<?php
namespace Phpcraft\Command;
use Phpcraft\Point3D;
interface CommandSender
{
	/**
	 * @return string
	 */
	function getName(): string;

	/**
	 * @param array|string $message
	 */
	function sendMessage($message): void;

	/**
	 * @param array|string $message
	 * @param string $permission
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
}
