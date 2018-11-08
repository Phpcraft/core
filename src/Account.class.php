<?php
namespace Phpcraft;
require_once __DIR__."/validate.php"; 
require_once __DIR__."/Utils.class.php"; 
/** A Mojang or Minecraft account. */
class Account
{
	private $name;
	private $username;
	private $profileId = null;
	private $accessToken = null;

	/**
	 * The constructor.
	 * @param $name The Mojang account email address or Minecraft account name.
	 */
	function __construct($name)
	{
		$this->name = $name;
		$this->username = $name;
	}

	/**
	 * Returns the email address of the Mojang account or the in-game name.
	 * @return string
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the in-game name.
	 * @return string
	 */
	function getUsername()
	{
		return $this->username;
	}

	/**
	 * Returns whether this account can be used to join servers in online mode.
	 * @return boolean
	 */
	function isOnline()
	{
		return $this->profileId != null && $this->accessToken != null;
	}

	/**
	 * Returns the selected profile ID or null if offline.
	 * @return string
	 */
	function getProfileId()
	{
		return $this->profileId;
	}

	/**
	 * Returns the access token for the account or null if offline.
	 * @return string
	 */
	function getAccessToken()
	{
		return $this->accessToken;
	}

	/**
	 * Logs in using .minecraft/launcher_profiles.json.
	 * @return boolean True on success.
	 */
	function loginUsingProfiles()
	{
		$profiles = Utils::getProfiles();
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
		if($foundAccount)
		{
			if(Utils::httpPOST("https://authserver.mojang.com/validate", [
				"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
				"clientToken" => $profiles["clientToken"]
			])["status"] == "403")
			{
				if($res = Utils::httpPOST("https://authserver.mojang.com/refresh", [
					"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
					"clientToken" => $profiles["clientToken"]
				]))
				{
					if(isset($res["accessToken"]))
					{
						$profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"] = $res["accessToken"];
						Utils::saveProfiles($profiles);
						return true;
					}
				}
				unset($profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]);
				Utils::saveProfiles($profiles);
				return false;
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Logs into Mojang or Minecraft account using password.
	 * This function will write the obtained access token into the .minecraft/launcher_profiles.json so you can avoid the password prompt in the future using Account::loginUsingProfiles().
	 * @param string $password
	 * @return string Error message. Empty on success.
	 */
	function login($password)
	{
		$profiles = Utils::getProfiles();
		if($res = Utils::httpPOST("https://authserver.mojang.com/authenticate", [
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
			Utils::saveProfiles($profiles);
			return "";
		}
		return "Invalid credentials";
	}
}
