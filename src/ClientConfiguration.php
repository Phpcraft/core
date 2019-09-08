<?php
namespace Phpcraft;
use hellsh\UUID;
use Phpcraft\Permission\Group;
class ClientConfiguration extends Configuration
{
	public $server;

	function __construct(Server &$server, $file = null)
	{
		parent::__construct($file);
		$this->server = $server;
	}

	function getGroupName(): string
	{
		return $this->get("group", "default");
	}

	function getGroup(): Group
	{
		$group = $this->server->getGroup($this->getGroupName());
		if($group !== null)
		{
			return $group;
		}
		$this->unset("group");
		return $this->server->getGroup("default");
	}

	function setGroup(string $name): ClientConfiguration
	{
		$this->set("group", $name);
		return $this;
	}

	function hasPermission(string $permission): bool
	{
		return $this->getGroup()->hasPermission($permission);
	}

	function isOnline(): bool
	{
		return $this->getPlayer() !== null;
	}

	/**
	 * @return ClientConnection|null
	 */
	function getPlayer()
	{
		return $this->file ? $this->server->getPlayer($this->getUUID()) : null;
	}

	/**
	 * @return UUID|null
	 */
	function getUUID()
	{
		return $this->file ? new UUID(substr($this->file, -37, 32)) : null;
	}

	/**
	 * @return string|null
	 */
	function getName()
	{
		return $this->file ? Phpcraft::$user_cache->get($this->getUUID()->toString(false)) : null;
	}
}
