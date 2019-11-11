<?php
namespace Phpcraft\Command;
use Phpcraft\Point3D;
interface CommandSender
{
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

	function hasPermission(string $permission): bool;

	function hasPosition(): bool;

	function getPosition(): ?Point3D;
}
