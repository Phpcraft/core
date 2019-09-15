<?php
namespace Phpcraft;
use Phpcraft\Realms\{Invite, Server};
/** A Mojang or Minecraft account. */
class Account
{
	/**
	 * The email address of the Mojang account or the in-game name if legacy.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * The in-game name.
	 *
	 * @var string $username
	 */
	public $username;
	/**
	 * The selected profile ID or null if offline.
	 *
	 * @var string $profileId
	 */
	public $profileId = null;
	/**
	 * The access token for the account or null if offline.
	 *
	 * @var string $accessToken
	 */
	public $accessToken = null;

	/**
	 * @param string $name The Mojang account email address or the in-game name if legacy or offline.
	 */
	function __construct(string $name)
	{
		$this->name = $name;
		$this->username = $name;
	}

	/**
	 * Returns whether this account can be used to join servers in online mode.
	 *
	 * @return boolean
	 */
	function isOnline()
	{
		return $this->profileId !== null && $this->accessToken !== null;
	}

	/**
	 * Asks the user of the CLI application to log-in by providing the password via STDIN.
	 * This function will block until the login succeeded.
	 *
	 * @param resource|null $stdin Your own STDIN stream, if you have already created one.
	 */
	function cliLogin($stdin = null)
	{
		if($this->loginUsingProfiles())
		{
			return;
		}
		$blocking_prev = null;
		if(is_resource($stdin))
		{
			$blocking_prev = stream_get_meta_data($stdin)["blocked"];
		}
		else
		{
			$stdin = fopen("php://stdin", "r") or die("Failed to open php://stdin\n");
		}
		if($blocking_prev !== true)
		{
			stream_set_blocking($stdin, true);
		}
		do
		{
			if(Phpcraft::isWindows())
			{
				echo "What's your account password? (visible) ";
			}
			else
			{
				/** @noinspection PhpComposerExtensionStubsInspection */
				readline_callback_handler_install("What's your account password? (hidden) ", function($input)
				{
				});
			}
			if(!($pass = trim(fgets($stdin))))
			{
				echo "No password provided.\n";
			}
			else if($error = $this->login($pass))
			{
				echo $error."\n";
			}
			else
			{
				echo "\n";
				break;
			}
		}
		while(true);
		if(!Phpcraft::isWindows())
		{
			/** @noinspection PhpComposerExtensionStubsInspection */
			readline_callback_handler_remove();
		}
		if($blocking_prev === null)
		{
			fclose($stdin);
		}
		else if($blocking_prev === false)
		{
			stream_set_blocking($stdin, $blocking_prev);
		}
	}

	/**
	 * Logs in using .minecraft/launcher_profiles.json.
	 *
	 * @return boolean True on success.
	 */
	function loginUsingProfiles()
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
	 *
	 * @param string $password
	 * @return string Error message. Empty on success.
	 */
	function login(string $password)
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

	/**
	 * Sends an HTTP request to the realms server.
	 *
	 * @param string $method The request method.
	 * @param string $path The path of the request, starting with a slash.
	 * @return bool|string The result of curl_exec.
	 */
	function sendRealmsRequest(string $method, string $path)
	{
		$ch = curl_init();
		echo "> $method $path";
		curl_setopt_array($ch, [
			CURLOPT_URL => "https://pc.realms.minecraft.net".$path,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Cache-Control: no-cache",
				"Cookie: sid=token:{$this->accessToken}:{$this->profileId};user={$this->username};version=".array_keys(Versions::releases(true))[0],
				"User-Agent: Phpcraft"
			]
		]);
		if(Phpcraft::isWindows())
		{
			curl_setopt($ch, CURLOPT_CAINFO, __DIR__."/cacert.pem");
		}
		$res = curl_exec($ch);
		echo " ".curl_getinfo($ch, CURLINFO_HTTP_CODE)."\n< $res\n";
		curl_close($ch);
		return $res;
	}

	/**
	 * Returns all realms invites this account currently has pending.
	 *
	 * @return Invite[]
	 */
	function getRealmsInvites()
	{
		$invites = [];
		foreach(json_decode($this->sendRealmsRequest("GET", "/invites/pending"), true)["invites"] as $invite)
		{
			array_push($invites, new Invite($this, $invite));
		}
		return $invites;
	}

	/**
	 * Returns all realms servers this account has joined or owns.
	 *
	 * @return Server[]
	 */
	function getRealmsServers()
	{
		$servers = [];
		foreach(json_decode($this->sendRealmsRequest("GET", "/worlds"), true)["servers"] as $server)
		{
			array_push($servers, new Server($this, $server));
		}
		return $servers;
	}
}
