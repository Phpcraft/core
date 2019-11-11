<?php
namespace Phpcraft;
use hellsh\UUID;
use Phpcraft\Permission\Group;
class ClientConfiguration extends Configuration
{
	/**
	 * @var Server $server
	 */
	public $server;
	/**
	 * The client's active connnection, if applicable.
	 *
	 * @var ClientConnection|null $con
	 */
	public $con;

	function __construct(Server &$server, ?ClientConnection $con = null, $file = null)
	{
		parent::__construct($file);
		$this->server = $server;
		$this->con = $con;
	}

	/**
	 * @param string $name
	 * @return ClientConfiguration
	 */
	function setGroup(string $name): ClientConfiguration
	{
		$this->set("group", $name);
		return $this;
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	function hasPermission(string $permission): bool
	{
		return $this->getGroup()
					->hasPermission($permission);
	}

	/**
	 * @return Group
	 */
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

	/**
	 * @return string
	 */
	function getGroupName(): string
	{
		return $this->get("group", "default");
	}

	/**
	 * @return bool
	 */
	function isOnline(): bool
	{
		return $this->getPlayer() !== null;
	}

	/**
	 * @return ClientConnection|null
	 */
	function getPlayer(): ?ClientConnection
	{
		return $this->con ?? ($this->file ? $this->server->getPlayer($this->getUUID()) : null);
	}

	/**
	 * @return UUID|null
	 */
	function getUUID(): ?UUID
	{
		return $this->con ? $this->con->uuid : ($this->file ? new UUID(substr($this->file, -37, 32)) : null);
	}

	/**
	 * @return string|null
	 */
	function getName(): ?string
	{
		return $this->con ? $this->con->username : ($this->file ? Phpcraft::$user_cache->get($this->getUUID()
																								  ->toString(false)) : null);
	}
}
