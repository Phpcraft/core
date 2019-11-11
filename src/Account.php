<?php /** @noinspection PhpComposerExtensionStubsInspection */
namespace Phpcraft;
use Phpcraft\Realms\
{Invite, Server};
/** A Mojang or Minecraft account. */
class Account
{
	static $allowed_username_characters = [];
	/**
	 * The in-game name.
	 *
	 * @var string $username
	 */
	public $username;
	/**
	 * The selected profile ID or null if offline.
	 *
	 * @var string|null $profileId
	 */
	public $profileId = null;
	/**
	 * The access token for the account or null if offline.
	 *
	 * @var string|null $accessToken
	 */
	public $accessToken = null;

	/**
	 * Contructs an Account for "offline mode" usage.
	 * For online usage, use Account::online or Account::cliLogin.
	 * Okay, I lied, if you call Account::loginUsingProfiles you <b><i>might</i></b> be able to convert such an instance into one for "online mode" usage.
	 *
	 * @param string $username The in-game name.
	 */
	function __construct(string $username)
	{
		$this->username = $username;
	}

	/**
	 * Asks the user of the CLI application for a logged-in Account for "online mode" usage.
	 *
	 * @param resource|null $stdin Your own STDIN stream or null if don't have one.
	 * @return Account
	 */
	static function cliLogin($stdin = null): Account
	{
		$profiles = Phpcraft::getProfiles();
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
		if(empty($profiles["authenticationDatabase"]))
		{
			$sel = 1;
		}
		else
		{
			echo "Choose a Mojang/Minecraft account to use:\n";
			$i = 1;
			foreach($profiles["authenticationDatabase"] as $account)
			{
				echo ($i++).") ".array_values($account["profiles"])[0]["displayName"]."\n";
			}
			echo "$i) Add an account\n";
			do
			{
				echo "Your selection: ";
				$sel = intval(trim(fgets($stdin)));
				if($sel < 1)
				{
					$sel = 0;
				}
				else if($sel > count($profiles["authenticationDatabase"]) + 1)
				{
					$sel = 0;
				}
			}
			while($sel == 0);
		}
		$account = null;
		if($sel > count($profiles["authenticationDatabase"]))
		{
			do
			{
				echo "What's your Mojang account email address? (username if unmigrated) ";
				$name = trim(fgets($stdin));
			}
			while(!$name);
			do
			{
				if(Phpcraft::isWindows())
				{
					echo "What's your account password? (visible) ";
				}
				else
				{
					readline_callback_handler_install("What's your account password? (hidden) ", function($input)
					{
					});
				}
				$password = trim(fgets($stdin));
				if(!Phpcraft::isWindows())
				{
					readline_callback_handler_remove();
					echo "\n";
				}
				if(!$password)
				{
					continue;
				}
				echo "Attempting to log in...";
				$account = Account::online($name, $password);
				if($account)
				{
					echo " Success!\n";
					break;
				}
				echo " Failed. Either the credentials were incorrect or the Mojang account doesn't own Minecraft.\n";
				echo "Would you like to try to enter the password again? [Y/n] ";
				if(trim(fgets($stdin)) == "n")
				{
					break;
				}
			}
			while(true);
		}
		else
		{
			echo "Throwing access token at Mojang... ";
			$account = new Account(array_values($profiles["authenticationDatabase"][array_keys($profiles["authenticationDatabase"])[$sel - 1]]["profiles"])[0]["displayName"]);
			if($account->loginUsingProfiles())
			{
				echo "Success!\n";
			}
			else
			{
				echo "Failed.\n";
				$account = null;
			}
		}
		if($blocking_prev === null)
		{
			fclose($stdin);
		}
		else if($blocking_prev === false)
		{
			stream_set_blocking($stdin, $blocking_prev);
		}
		return $account ?? Account::cliLogin($stdin);
	}

	/**
	 * Creates a logged-in Account instance for "online mode" usage.
	 *
	 * @param string $name The Mojang account email address or in-game name if unmigrated.
	 * @param string $password The account's password.
	 * @return Account|null
	 */
	static function online(string $name, string $password): ?Account
	{
		$profiles = Phpcraft::getProfiles();
		$res = Phpcraft::httpPOST("https://authserver.mojang.com/authenticate", [
			"agent" => [
				"name" => "Minecraft",
				"version" => 1
			],
			"username" => $name,
			"password" => $password,
			"clientToken" => $profiles["clientToken"],
			"requestUser" => true
		]);
		if($res && isset($res["selectedProfile"]))
		{
			$account = new Account($res["selectedProfile"]["name"]);
			$account->profileId = $res["selectedProfile"]["id"];
			$account->accessToken = $res["accessToken"];
			$profiles["authenticationDatabase"][$res["user"]["id"]] = [
				"accessToken" => $account->accessToken,
				"profiles" => [
					$account->profileId => [
						"displayName" => $account->username
					]
				]
			];
			Phpcraft::saveProfiles($profiles);
			return $account;
		}
		return null;
	}

	/**
	 * Attempts to turns an "offline mode" instance into an "online mode" instance using .minecraft/launcher_profiles.json.
	 *
	 * @return boolean Whether it was successful.
	 */
	function loginUsingProfiles(): bool
	{
		$i = Account::getAccountIdFromProfileName($this->username);
		if($i === null)
		{
			return false;
		}
		$profiles = Phpcraft::getProfiles();
		$this->accessToken = $profiles["authenticationDatabase"][$i]["accessToken"];
		if(Phpcraft::httpPOST("https://authserver.mojang.com/validate", [
				"accessToken" => $this->accessToken,
				"clientToken" => $profiles["clientToken"]
			])["status"] == "204")
		{
			$this->profileId = array_keys($profiles["authenticationDatabase"][$i]["profiles"])[0];
			return true;
		}
		$res = $res = Phpcraft::httpPOST("https://authserver.mojang.com/refresh", [
			"accessToken" => $this->accessToken,
			"clientToken" => $profiles["clientToken"]
		]);
		if($res && isset($res["accessToken"]))
		{
			$this->accessToken = $res["accessToken"];
			$this->profileId = array_keys($profiles["authenticationDatabase"][$i]["profiles"])[0];
			$profiles["authenticationDatabase"][$i]["accessToken"] = $this->accessToken;
			Phpcraft::saveProfiles($profiles);
			return false;
		}
		unset($profiles["authenticationDatabase"][$i]);
		Phpcraft::saveProfiles($profiles);
		$this->accessToken = null;
		return true;
	}

	static function getAccountIdFromProfileName(string $profile_name): ?string
	{
		$profiles = Phpcraft::getProfiles();
		foreach($profiles["authenticationDatabase"] as $account_id => $account)
		{
			foreach($account["profiles"] as $uuid => $data)
			{
				if($data["displayName"] == $profile_name)
				{
					return $account_id;
				}
			}
		}
		return null;
	}

	/**
	 * Returns true if the given username is valid.
	 *
	 * @param string $username
	 * @return bool
	 */
	static function validateUsername(string $username): bool
	{
		if(strlen($username) < 3 || strlen($username) > 16)
		{
			return false;
		}
		foreach(str_split($username) as $char)
		{
			if(!in_array($char, self::$allowed_username_characters))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns whether this account can be used to join servers in online mode.
	 *
	 * @return boolean
	 */
	function isOnline(): bool
	{
		return $this->profileId !== null && $this->accessToken !== null;
	}

	/**
	 * Returns all realms invites this account currently has pending.
	 *
	 * @return array<Invite>
	 */
	function getRealmsInvites(): array
	{
		$invites = [];
		foreach(json_decode($this->sendRealmsRequest("GET", "/invites/pending"), true)["invites"] as $invite)
		{
			array_push($invites, new Invite($this, $invite));
		}
		return $invites;
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
	 * Returns all realms servers this account has joined or owns.
	 *
	 * @return array<Server>
	 */
	function getRealmsServers(): array
	{
		$servers = [];
		foreach(json_decode($this->sendRealmsRequest("GET", "/worlds"), true)["servers"] as $server)
		{
			array_push($servers, new Server($this, $server));
		}
		return $servers;
	}
}

Account::$allowed_username_characters = [
	"_",
	"0",
	"1",
	"2",
	"3",
	"4",
	"5",
	"6",
	"7",
	"8",
	"9"
];
foreach(range("a", "z") as $char)
{
	array_push(Account::$allowed_username_characters, $char);
}
foreach(range("A", "Z") as $char)
{
	array_push(Account::$allowed_username_characters, $char);
}
