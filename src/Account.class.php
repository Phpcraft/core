<?php
namespace Phpcraft;
/** A Mojang or Minecraft account. */
class Account
{
	/**
	 * The email address of the Mojang account or the in-game name if legacy.
	 * @var string $name
	 */
	public $name;
	/**
	 * The in-game name.
	 * @var string $username
	 */
	public $username;
	/**
	 * The selected profile ID or null if offline.
	 * @var string $profileId
	 */
	public $profileId = null;
	/**
	 * The access token for the account or null if offline.
	 * @var string $accessToken
	 */
	public $accessToken = null;

	/**
	 * @param string $name The Mojang account email address or the in-game name if legacy or offline.
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
		$this->username = $name;
	}

	/**
	 * Returns whether this account can be used to join servers in online mode.
	 * @return boolean
	 */
	public function isOnline()
	{
		return $this->profileId !== null && $this->accessToken !== null;
	}

	/**
	 * Logs in using .minecraft/launcher_profiles.json.
	 * @return boolean True on success.
	 */
	public function loginUsingProfiles()
	{
		$profiles = Phpcraft::getProfiles();
		$foundAccount = false;
		foreach($profiles["authenticationDatabase"] as $n => $v)
		{
			if($v["username"] == $this->name)
			{
				foreach($v["profiles"] as $u => $d)
				{
					$profiles["selectedUser"]["profile"] = $this->profileId = $u;
					$this->username = $d["displayName"];
					break;
				}
				$profiles["selectedUser"]["account"] = $n;
				$this->accessToken = $v["accessToken"];
				$foundAccount = true;
				break;
			}
			else
			{
				foreach($v["profiles"] as $u => $d)
				{
					if($d["displayName"] == $this->username)
					{
						$profiles["selectedUser"]["profile"] = $this->profileId = $u;
						$foundAccount = true;
						break;
					}
				}
				if($foundAccount)
				{
					$profiles["selectedUser"]["account"] = $n;
					$this->name = $v["username"];
					$this->accessToken = $v["accessToken"];
					break;
				}
			}
		}
		if(!$foundAccount)
		{
			return false;
		}
		if(Phpcraft::httpPOST("https://authserver.mojang.com/validate", [
			"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
			"clientToken" => $profiles["clientToken"]
		])["status"] == "204")
		{
			return true;
		}
		if($res = Phpcraft::httpPOST("https://authserver.mojang.com/refresh", [
			"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
			"clientToken" => $profiles["clientToken"]
		]) && isset($res["accessToken"]))
		{
			$profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"] = $res["accessToken"];
			Phpcraft::saveProfiles($profiles);
			return true;
		}
		unset($profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]);
		Phpcraft::saveProfiles($profiles);
		return false;
	}

	/**
	 * Logs into Mojang or Minecraft account using password.
	 * This function will write the obtained access token into the .minecraft/launcher_profiles.json so you can avoid the password prompt in the future using Account::loginUsingProfiles().
	 * @param string $password
	 * @return string Error message. Empty on success.
	 */
	public function login(string $password)
	{
		$profiles = Phpcraft::getProfiles();
		if($res = Phpcraft::httpPOST("https://authserver.mojang.com/authenticate", [
			"agent" => [
				"name" => "Minecraft",
				"version" => 1
			],
			"username" => $this->name,
			"password" => $password,
			"clientToken" => $profiles["clientToken"],
			"requestUser" => true
		]))
		{
			if(!isset($res["selectedProfile"]))
			{
				return "Your Mojang account does not have a Minecraft license.";
			}
			$profiles["selectedUser"] = [
				"account" => $res["user"]["id"],
				"profile" => $this->profileId = $res["selectedProfile"]["id"]
			];
			$profiles["authenticationDatabase"][$res["user"]["id"]] = [
				"accessToken" => $this->accessToken = $res["accessToken"],
				"username" => $this->name,
				"profiles" => [
					$this->profileId => [
						"displayName" => $this->username = $res["selectedProfile"]["name"]
					]
				]
			];
			Phpcraft::saveProfiles($profiles);
			return "";
		}
		return "Invalid credentials";
	}
}
