<?php
namespace Phpcraft\Permission;
use Phpcraft\Server;
class Group
{
	/**
	 * @var array $data
	 */
	public $data;
	private $server;
	private $permissions;

	function __construct(Server &$server, array $data)
	{
		$this->server = $server;
		$this->data = $data;
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	function hasPermission(string $permission): bool
	{
		return in_array($permission, $this->getPermissions()) || in_array("everything", $this->permissions);
	}

	/**
	 * @return array<string>
	 */
	function getPermissions(): array
	{
		if($this->permissions === null)
		{
			$this->permissions = [];
			if(array_key_exists("inherit", $this->data))
			{
				if(is_string($this->data["inherit"]))
				{
					$this->permissions = $this->server->getGroup($this->data["inherit"])
													  ->getPermissions();
				}
				else
				{
					foreach($this->data["inherit"] as $group)
					{
						$this->permissions = array_merge($this->permissions, $this->server->getGroup($group)
																						  ->getPermissions());
					}
				}
			}
			if(array_key_exists("allow", $this->data))
			{
				if(is_string($this->data["allow"]))
				{
					array_push($this->permissions, $this->data["allow"]);
				}
				else
				{
					$this->permissions = array_merge($this->permissions, $this->data["allow"]);
				}
			}
			$this->permissions = array_unique($this->permissions);
		}
		return $this->permissions;
	}
}
