<?php
/**
 * Phpcraft
 * @author Tim "timmyRS" Speckhals
 */
namespace Phpcraft;

if(get_included_files()[0] == __FILE__)
{
	die("Phpcraft is not to be executed. Execute client.php, server.php, listping.php, or create something yourself and `require Phpcraft.php`.\n");
}
if(empty($argv))
{
	die("This is for CLI PHP. Connect to your server via SSH and use `php ".get_included_files()[0]."` to execute it.\n");
}
if(PHP_INT_SIZE < 8)
{
	die("Phpcraft requires 64-bit PHP.\n");
}
if(version_compare(phpversion(), "7.0.15", "<"))
{
	die("Phpcraft requires PHP 7.0.15 or above. Try `apt-get install php7.0-cli`.\n");
}
if(!extension_loaded("mbstring"))
{
	die("Phpcraft requires mbstring. Try `apt-get install php-mbstring` or check your PHP configuration.\n");
}

/** Utilities. */
class Utils
{
	private static $minecraft_folder = null;
	private static $protocol_versions = [
		47 => "1.8", // 1.8 - 1.8.9 have the same protocol ID
		107 => "1.9",
		108 => "1.9.1",
		109 => "1.9.2",
		110 => "1.9.4", // 1.9.3 and 1.9.4 have the same protocol ID
		210 => "1.10", // 1.10 - 1.10.2 have the same protocol ID
		315 => "1.11",
		316 => "1.11.2", // 1.11.1 and 1.11.2 have the same protocol ID
		335 => "1.12",
		336 => "17w31a",
		337 => "1.12.1-pre1",
		338 => "1.12.1",
		339 => "1.12.2-pre2", // 1.12.2-pre1 and 1.12.2-pre2 have the same protocol ID
		340 => "1.12.2",
		393 => "1.13",
		401 => "1.13.1",
		402 => "1.13.2-pre1",
		403 => "1.13.2-pre2",
		404 => "1.13.2"
	];

	/**
	 * Returns the path of the .minecraft folder without a slash at the end.
	 * @return string
	 */
	static function getMinecraftFolder()
	{
		if(Utils::$minecraft_folder === null)
		{
			if(stristr(PHP_OS, "LINUX"))
			{
				Utils::$minecraft_folder = getenv("HOME")."/.minecraft";
			}
			else if(stristr(PHP_OS, "DAR"))
			{
				Utils::$minecraft_folder = getenv("HOME")."/Library/Application Support/minecraft";
			}
			else if(stristr(PHP_OS, "WIN"))
			{
				Utils::$minecraft_folder = getenv("APPDATA")."\\.minecraft";
			}
			else
			{
				Utils::$minecraft_folder = __DIR__."/.minecraft";
			}
			if(!file_exists(Utils::$minecraft_folder) || !is_dir(Utils::$minecraft_folder))
			{
				mkdir(Utils::$minecraft_folder);
			}
		}
		return Utils::$minecraft_folder;
	}

	/**
	 * Returns the path of the .minecraft/launcher_profiles.json.
	 * @return string
	 */
	static function getProfilesFile()
	{
		return Utils::getMinecraftFolder()."/launcher_profiles.json";
	}

	/**
	 * Returns the contents of the .minecraft/launcher_profiles.json with some values being set if they are unset.
	 * @return array
	 * @see Utils::getProfilesFile()
	 * @see Utils::saveProfiles()
	 */
	static function getProfiles()
	{
		$profiles_file = Utils::getProfilesFile();
		if(file_exists($profiles_file) && is_file($profiles_file))
		{
			$profiles = json_decode(file_get_contents($profiles_file), true);
		}
		else
		{
			$profiles = [];
		}
		if(empty($profiles["clientToken"]))
		{
			$profiles["clientToken"] = Utils::generateUUIDv4();
		}
		if(!isset($profiles["selectedUser"]))
		{
			$profiles["selectedUser"] = [];
		}
		if(!isset($profiles["authenticationDatabase"]))
		{
			$profiles["authenticationDatabase"] = [];
		}
		return $profiles;
	}

	/**
	 * Saves the profiles array into the .minecraft/launcher_profiles.json.
	 * @param array $profiles
	 * @return void
	 */
	static function saveProfiles($profiles)
	{
		file_put_contents(Utils::getProfilesFile(), json_encode($profiles, JSON_PRETTY_PRINT));
	}

	/**
	 * Validates an in-game name.
	 * @param string $name
	 * @return boolean True if the name is valid.
	 */
	static function validateName($name)
	{
		if(strlen($name) < 3 || strlen($name) > 16)
		{
			return false;
		}
		$allowed_characters = ["_", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
		foreach(range("a", "z") as $char)
		{
			array_push($allowed_characters, $char);
		}
		foreach(range("A", "Z") as $char)
		{
			array_push($allowed_characters, $char);
		}
		foreach(str_split($name) as $char)
		{
			if(!in_array($char, $allowed_characters))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns an array of extensions missing to enable online mode.
	 * If you want to enable online mode, GMP, openssl, and mcrypt are required. This function returns a string array of all extensions that are missing. Therefore, an empty array means all required extensions are installed.
	 * @return array
	 */
	static function getExtensionsMissingToGoOnline()
	{
		$extensions_needed = [];
		if(!extension_loaded("gmp"))
		{
			array_push($extensions_needed, "GMP");
		}
		if(!extension_loaded("openssl"))
		{
			array_push($extensions_needed, "openssl");
		}
		$mcrypt = false;
		foreach(stream_get_filters() as $filter)
		{
			if(stristr($filter, "mcrypt"))
			{
				$mcrypt = true;
			}
		}
		if(!$mcrypt)
		{
			array_push($extensions_needed, "mcrypt");
		}
		return $extensions_needed;
	}

	/**
	 * Generates a random UUID (UUIDv4).
	 * @param boolean $withHypens
	 * @return string
	 */
	static function generateUUIDv4($withHypens = false)
	{
		return sprintf($withHypens ? "%04x%04x-%04x-%04x-%04x-%04x%04x%04x" : "%04x%04x%04x%04x%04x%04x%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), (mt_rand(0, 0x0fff) | 0x4000), (mt_rand(0, 0x3fff) | 0x8000), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	/**
	 * Adds hypens to a UUID.
	 * @param string $uuid
	 * @return string
	 */
	public function addHypensToUUID($uuid)
	{
		return substr($uuid, 0, 8)."-".substr($uuid, 8, 4)."-".substr($uuid, 12, 4)."-".substr($uuid, 16, 4)."-".substr($uuid, 20);
	}

	/**
	 * Sends an HTTP POST request with a JSON payload.
	 * The response will always contain a "status" value which will be the HTTP response code, e.g. 200.
	 * @param string $url
	 * @param array $data
	 * @return array
	 */
	static function httpPOST($url, $data)
	{
		$res = @file_get_contents($url, false, stream_context_create([
			"http" => [
				"header" => "Content-type: application/json\r\n",
				"method" => "POST",
				"content" => json_encode($data)
			]
		]));
		if($res == "")
		{
			$res = [];
		}
		else
		{
			$res = json_decode($res, true);
		}
		$res["status"] = explode(" ", $http_response_header[0])[1];
		return $res;
	}

	/**
	 * Resolves the given address.
	 * @param string $server The server addres, e.g. localhost
	 * @return string The resolved address, e.g. localhost:25565
	 */
	static function resolve($server)
	{
		$arr = explode(":", $server);
		if(count($arr) > 1)
		{
			return Utils::resolveName($arr[0], false).":".$arr[1];
		}
		return Utils::resolveName($server, true);
	}

	private static function resolveName($server, $withPort = true)
	{
		if(ip2long($server) === false && $res = @dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
		{
			$i = array_rand($res);
			return Utils::resolveName($res[$i]["target"], false).($withPort ? ":".$res[$i]["port"] : "");
		}
		return $server.($withPort ? ":25565" : "");
	}

	/**
	 * Converts an integer into a VarInt binary string.
	 * @param integer $value
	 * @return string
	 */
	static function intToVarInt($value)
	{
		$bytes = "";
		global $write_buffer;
		do
		{
			$temp = ($value & 0b01111111);
			$value = (($value >> 7) & 0b01111111);
			if($value != 0)
			{
				$temp |= 0b10000000;
			}
			$bytes .= pack("c", $temp);
		}
		while($value != 0);
		return $bytes;
	}

	/**
	 * Returns whether a given protocol version is supported.
	 * @param integer $protocol_version e.g., 340
	 * @return boolean
	 */
	static function isProtocolVersionSupported($protocol_version)
	{
		return isset(Utils::$protocol_versions[$protocol_version]);
	}

	/**
	 * Returns the Minecraft version corresponding to the given protocol version.
	 * @param integer $protocol_version e.g., 340 for 1.12.2
	 * @return string The Minecraft version or null if the protocol version is not supported.
	 */
	static function getMinecraftVersionFromProtocolVersion($protocol_version)
	{
		return (isset(Utils::$protocol_versions[$protocol_version]) ? Utils::$protocol_versions[$protocol_version] : null);
	}

	/**
	 * Returns whether a given Minecraft version is supported.
	 * @param string $minecraft_version e.g., 1.12.2
	 * @return boolean
	 */
	static function isMinecraftVersionSupported($minecraft_version)
	{
		return in_array($minecraft_version, Utils::$protocol_versions);
	}

	/**
	 * Returns the Minecraft version corresponding to the given protocol version.
	 * @param string $minecraft_version e.g., 1.12.2 for 340
	 * @return integer The protocol version or null if the Minecraft version is not supported.
	 */
	static function getProtocolVersionFromMinecraftVersion($minecraft_version)
	{
		foreach(Utils::$protocol_versions as $protocol_version => $v)
		{
			if($v == $minecraft_version)
			{
				return $protocol_version;
			}
		}
		return null;
	}

	/**
	 * Generates a Minecraft-style SHA1 hash.
	 * @param string $str
	 * @return string
	 */
	static function sha1($str)
	{
		$gmp = gmp_import(sha1($str, true));
		if(gmp_cmp($gmp, gmp_init("0x8000000000000000000000000000000000000000")) >= 0)
		{
			$gmp = gmp_mul(gmp_add(gmp_xor($gmp, gmp_init("0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")), gmp_init(1)), gmp_init(-1));
		}
		return gmp_strval($gmp, 16);
	}

	/**
	 * Converts a string using § format codes into a chat object.
	 * @param string $str
	 * @param boolean $allowAnd When true, '&' will be handled like '§'.
	 * @param integer $i Ignore this parameter.
	 * @param boolean $child Ignore this parameter.
	 */
	static function textToChat($str, $allowAnd = false, &$i = 0, $child = false)
	{
		if(strpos($str, "§") === false && (!$allowAnd || strpos($str, "&") === false))
		{
			return ["text" => $str];
		}
		if(!$child && $i == 0 && (strpos(mb_substr($str, 2, null, "utf-8"), "§r") !== false || ($allowAnd && strpos(mb_substr($str, 2, null, "utf-8"), "&r") !== false)))
		{
			$extras = [];
			while($i < mb_strlen($str, "utf-8"))
			{
				array_push($extras, Utils::textToChat($str, $allowAnd, $i, true));
				$i++;
			}
			return ["text" => "", "extra" => $extras];
		}
		$colors = [
			"0" => "black",
			"1" => "dark_blue",
			"2" => "dark_green",
			"3" => "dark_aqua",
			"4" => "dark_red",
			"5" => "dark_purple",
			"6" => "gold",
			"7" => "gray",
			"8" => "dark_gray",
			"9" => "blue",
			"a" => "green",
			"b" => "aqua",
			"c" => "red",
			"d" => "light_purple",
			"e" => "yellow",
			"f" => "white"
		];
		$chat = ["text" => ""];
		$lastWasParagraph = false;
		while($i < mb_strlen($str, "utf-8"))
		{
			$c = mb_substr($str, $i, 1, "utf-8");
			if($c == "§" || ($allowAnd && $c == "&"))
			{
				$lastWasParagraph = true;
			}
			else if($lastWasParagraph)
			{
				$lastWasParagraph = false;
				if($child && $c == "r")
				{
					return $chat;
				}
				if($chat["text"] == "")
				{
					if($c == "r")
					{
						unset($chat["obfuscated"]);
						unset($chat["bold"]);
						unset($chat["strikethrough"]);
						unset($chat["underlined"]);
						unset($chat["italic"]);
						unset($chat["color"]);
					}
					else if($c == "k")
					{
						$chat["obfuscated"] = true;
					}
					else if($c == "l")
					{
						$chat["bold"] = true;
					}
					else if($c == "m")
					{
						$chat["strikethrough"] = true;
					}
					else if($c == "n")
					{
						$chat["underlined"] = true;
					}
					else if($c == "o")
					{
						$chat["italic"] = true;
					}
					else if(isset($colors[$c]))
					{
						$chat["color"] = $colors[$c];
					}
				}
				else
				{
					$i--;
					$component = Utils::textToChat($str, $allowAnd, $i, true);
					if(!empty($component["text"]) || count($component) > 1)
					{
						if(empty($chat["extra"]))
						{
							$chat["extra"] = [$component];
						}
						else
						{
							array_push($chat["extra"], $component);
						}
					}
				}
			}
			else
			{
				$chat["text"] .= $c;
			}
			$i++;
		}
		return $chat;
	}

	/**
	 * Converts a chat object into text with ANSI escape codes so it will be colorful in the console, as well.
	 * @param array|string $chat The chat object as an array or a string.
	 * @param array $translations The translations array so translated messages look proper.
	 * @param mixed $parent The parent chat object so styling is properly inherited. You don't need to set this.
	 * @return string
	 */
	static function chatToANSIText($chat, $translations = null, $parent = false)
	{
		if($translations == null)
		{
			$translations = [
				"chat.type.text" => "<%s> %s",
				"chat.type.announcement" => "[%s] %s",
				"multiplayer.player.joined" => "%s joined the game",
				"multiplayer.player.left" => "%s left the game"
			];
		}
		if(gettype($chat) == "string")
		{
			if(strpos($chat, "§") === false)
			{
				return $chat;
			}
			$chat = Utils::textToChat($chat);
		}
		if($parent === false)
		{
			$child = false;
			$parent = [];
		}
		else
		{
			$child = true;
		}
		$attributes = [
			"bold" => "1",
			"italic" => "3",
			"underlined" => "4",
			"obfuscated" => "8",
			"strikethrough" => "9"
		];
		$text = "\x1B[0";
		foreach($attributes as $n => $v)
		{
			if(!isset($chat[$n]))
			{
				if(isset($parent[$n]))
				{
					$chat[$n] = $parent[$n];
				}
			}
			if(isset($chat[$n]) && $chat[$n])
			{
				$text .= ";{$v}";
			}
		}
		if(!isset($chat["color"]))
		{
			if(isset($parent["color"]))
			{
				$chat["color"] = $parent["color"];
			}
		}
		if(isset($chat["color"]))
		{
			$colors = [
				"black" => "30;107", // Using a white background on black text
				"dark_blue" => "34",
				"dark_green" => "32",
				"dark_aqua" => "36",
				"dark_red" => "31",
				"dark_purple" => "35",
				"gold" => "33",
				"gray" => "37",
				"dark_gray" => "90",
				"blue" => "94",
				"green" => "92",
				"aqua" => "96",
				"red" => "91",
				"light_purple" => "95",
				"yellow" => "93",
				"white" => "97"
			];
			if(isset($colors[$chat["color"]]))
			{
				$text .= ";".$colors[$chat["color"]];
			}
		}
		$text .= "m";
		if(isset($chat["translate"]))
		{
			$raw;
			if(isset($translations[$chat["translate"]]))
			{
				$raw = $translations[$chat["translate"]];
			}
			else
			{
				$raw = $chat["translate"];
			}
			if(isset($chat["with"]))
			{
				$with = [];
				foreach($chat["with"] as $extra)
				{
					array_push($with, Utils::chatToANSIText($extra, $translations, $chat));
				}
				if(($formatted = @vsprintf($raw, $with)) !== false)
				{
					$raw = $formatted;
				}
			}
			$text .= $raw;
		}
		else if(isset($chat["text"]))
		{
			if(strpos($chat["text"], "§") !== false)
			{
				$chat = Utils::textToChat($chat["text"]) + $chat;
			}
			$text .= $chat["text"];
		}
		if(!$child)
		{
			$text .= "\x1B[0;97;40m";
		}
		if(isset($chat["extra"]))
		{
			foreach($chat["extra"] as $extra)
			{
				$text .= Utils::chatToANSIText($extra, $translations, $chat);
			}
			if(!$child)
			{
				$text .= "\x1B[0;97;40m";
			}
		}
		return $text;
	}
}

/** A utility for interfacing with the user. */
class UserInterface
{
	private $title;
	private $optional_info;
	private $stdin;
	/**
	 * The string displayed before the user's input, e.g. `$ `
	 * @var string
	 */
	public $input_prefix = "";
	private $input_buffer = "";
	private $cursorpos = 1;
	/**
	 * The function called when the user presses the tabulator key with the currently selected word as parameter. The return should be an array of possible completions.
	 * @var function
	 */
	public $tabcomplete_function = null;
	private $screen_scroll = 0;
	private $chat_log = [];
	private $next_render = 0;

	/**
	 * Returns an array of dependencies required for spinning up a UI which are missing on the system.
	 * To spin up a UI, users need tput and readline, which _should_ be available because WINNT is no longer supported.
	 * Regardless, make sure the return of this function is an empty array before you initalize a UserInterface.
	 * @return array
	 */
	static function getMissingDependencies()
	{
		$dependencies = [];
		$res = trim(shell_exec("tput cols"));
		if($res != intval($res))
		{
			array_push($dependencies, "tput");
		}
		if(!extension_loaded("readline"))
		{
			array_push($dependencies, "readline");
		}
		return $dependencies;
	}

	/**
	 * The constructor.
	 * Note that from this point forward user input is in the hands of the UI until UserInterface::dispose() is called.
	 * @param string $title The title displayed at the top left.
	 * @param string $optional_info Displayed at the top right, if possible.
	 */
	function __construct($title, $optional_info = "")
	{
		$this->title = $title;
		$this->optional_info = $optional_info;
		echo "\x1B[2J";
		$this->stdin = fopen("php://stdin", "r");
		stream_set_blocking($this->stdin, false);
		readline_callback_handler_remove();
		readline_callback_handler_install("", function(){}); // This allows reading STDIN on a char-by-char basis, instead of a line-by-line basis.
		set_error_handler(function($severity, $message, $file, $line)
		{
			if(error_reporting() & $severity)
			{
				$this->add("{$message} at {$file}:{$line}")->render();
			}
		});
		set_exception_handler(function($e)
		{
			$this->add("{$e->getMessage()} (".get_class($e).") at {$e->getFile()}:{$e->getLine()}")->render();
		});
	}

	/**
	 * Disposes of the UI.
	 * @return void
	 */
	function dispose()
	{
		fclose($this->stdin);
		readline_callback_handler_remove();
	}

	/**
	 * Renders the UI.
	 * @param boolean $return_input Set to true when you are ready to potentially receive a string as return.
	 * @return void|string If the user has submitted something and $return_input is true, a string containing their message is returned.
	 */
	function render($return_input = false)
	{
		$read = [$this->stdin];
		$null = null;
		if(stream_select($read, $null, $null, 0))
		{
			while(($char = fgetc($this->stdin)) !== FALSE)
			{
				if($char == "\n")
				{
					if($this->input_buffer == "")
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						if(!$return_input)
						{
							break;
						}
						$line = trim($this->input_buffer);
						$this->input_buffer = "";
						$this->cursorpos = 1;
						$this->next_render = 0;
						return $line;
					}
				}
				else if($char == "\x7F") // Backspace
				{
					if($this->cursorpos == 1 || $this->input_buffer == "")
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						$this->cursorpos--;
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, NULL, "utf-8");
						$this->next_render = 0;
					}
				}
				else if($char == "\t") // Tabulator
				{
					$tabcomplete_function = $this->tabcomplete_function;
					if($tabcomplete_function == NULL)
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						$buffer_ = "";
						$completed = false;
						foreach(explode(" ", $this->input_buffer) as $word)
						{
							if($completed)
							{
								$buffer_ .= " ".$word;
								continue;
							}
							if(mb_strlen($buffer_, "utf-8") + mb_strlen($word, "utf-8") + 2 < $this->cursorpos)
							{
								if($buffer_ == "")
								{
									$buffer_ = $word;
								}
								else
								{
									$buffer_ .= " ".$word;
								}
								continue;
							}
							$res = $tabcomplete_function($word);
							if(count($res) == 1)
							{
								if($buffer_ == "")
								{
									$buffer_ = $res[0];
								}
								else
								{
									$buffer_ .= " ".$res[0];
								}
								$this->cursorpos += strlen($res[0]) - mb_strlen($word, "utf-8");
							}
							else
							{
								if(count($res) > 1)
								{
									$this->add(join(", ", $res));
								}
								if($buffer_ == "")
								{
									$buffer_ = $word;
								}
								else
								{
									$buffer_ .= " ".$word;
								}
							}
							$completed = true;
						}
						if($this->cursorpos > mb_strlen($buffer_, "utf-8") + 1)
						{
							$this->cursorpos = mb_strlen($buffer_, "utf-8") + 1;
						}
						$this->input_buffer = $buffer_;
						$this->next_render = 0;
					}
				}
				else
				{
					if($char == "\x1B")
					{
						$char = "^";
					}
					$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").$char.mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
					$this->cursorpos++;
					if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[1~") // Pos1
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = 1;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[3~") // Delete
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, NULL, "utf-8");
						if($this->input_buffer == "" || $this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							echo "\x07"; // Bell/Alert
						}
						$this->cursorpos -= 4;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[4~") // End
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[5~") // Screen Up
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						$this->screen_scroll++;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[6~") // Screen Down
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						if($this->screen_scroll == 0)
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$this->screen_scroll--;
						}
					}
					else if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[D") // Arrow Left
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						if($this->cursorpos == 1)
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$this->cursorpos--;
						}
					}
					else if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[C") // Arrow Right
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						if($this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$this->cursorpos++;
						}
					}
					$this->next_render = 0;
				}
			}
		}
		if($this->next_render < microtime(true))
		{
			$width = intval(trim(shell_exec("tput cols")));
			$height = intval(trim(shell_exec("tput lines")));
			echo "\x1B[1;1H\x1B[30;107m{$this->title}";
			$len = mb_strlen($this->title, "utf-8");
			if($width > ($len + mb_strlen($this->optional_info, "utf-8")))
			{
				echo str_repeat(" ", $width - ($len + mb_strlen($this->optional_info, "utf-8"))).$this->optional_info;
			}
			else if($width > $len)
			{
				echo str_repeat(" ", $width - $len);
			}
			echo "\x1B[97;40m".str_repeat(" ", ($height - 1) * $width)."\x1B[1;2H";
			$gol_tahc = array_reverse($this->chat_log);
			$input_height = floor(mb_strlen($this->input_prefix.$this->input_buffer, "utf-8") / $width);
			$j = $this->screen_scroll;
			if($j > 100 - $height)
			{
				$j = 100 - $height;
				$this->screen_scroll = 100 - $height;
				echo "\x07"; // Bell/Alert
			}
			for($i = $height - $input_height - 1; $i > 1; $i--)
			{
				$message = @$gol_tahc[$j++];
				$len = mb_strlen(preg_replace('/\x1B\[[0-9]{1,3}(\;[0-9]{1,3})*m/i', "", $message), "utf-8");
				if($len > $width)
				{
					$i -= floor(mb_strlen($message, "utf-8") / $width);
				}
				echo "\x1B[{$i};1H";
				echo "{$message}\x1B[97;40m";
			}
			echo "\x1B[".($height - $input_height).";1H\x1B[97;40m".$this->input_prefix.$this->input_buffer;
			$cursor_width = (mb_strlen($this->input_prefix, "utf-8") + $this->cursorpos);
			if($cursor_width < $width)
			{
				$cursor_height = $height;				
			}
			else
			{
				$overflows = floor(($cursor_width - 1) / $width);
				$cursor_height = $height - $input_height + $overflows;
				if($overflows > 0)
				{
					$cursor_width -= floor($width / $overflows);
				}
			}
			echo "\x1B[{$cursor_height};{$cursor_width}H";
			if(count($this->chat_log) > 100)
			{
				array_shift($this->chat_log);
			}
			$this->next_render = microtime(true) + 0.1;
		}
	}

	/**
	 * Adds a message to the chat log.
	 * @return $this
	 */
	function add($message)
	{
		array_push($this->chat_log, $message);
		return $this;
	}

	/**
	 * Appends to the last message in the chat log.
	 * @return $this
	 */
	function append($appendix)
	{
		$this->chat_log[count($this->chat_log) - 1] .= $appendix;
		return $this;
	}
}

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

/** The class used for exceptions thrown by Phpcraft functions. */
class Exception extends \Exception
{
	/**
	 * The constructor.
	 * @param string $message The error message.
	 */
	function __construct($message)
	{
		parent::__construct($message);
	}
}

/**
 * A wrapper to read and write from streams.
 * The Connection object can also be utilized without a stream:
 * <pre>$con = new Connection($protocol_version);
 * $packet = new ChatMessagePacket(["text" => "Hello, world!"]);
 * $packet->send($con);
 * echo hex2bin($con->getAndClearWriteBuffer())."\n";</pre>
 */
class Connection
{
	/**
	 * The stream of the connection of null.
	 * @var resource $stream
	 */
	protected $stream;
	/**
	 * The protocol version that is used for this connection.
	 * @var integer $protocol_version
	 */
	protected $protocol_version;
	/**
	 * The amount of bytes a packet needs for it to be compressed as an integer or -1 if disabled.
	 * @var integer $compression_threshold
	 * @see Connection::getCompressionThreshold()
	 */
	protected $compression_threshold = false;
	/**
	 * The state of the connection.
	 * 1 stands for status, 2 for logging in and 3 for playing.
	 * @var integer $state
	 * @see Connection::getState()
	 */
	protected $state;
	/**
	 * The write buffer binary string.
	 * @var string $write_buffer
	 * @see Connection::getWriteBuffer()
	 * @see Connection::getAndClearWriteBuffer()
	 * @see Connection::clearWriteBuffer()
	 */
	protected $write_buffer = "";
	/**
	 * The read buffer binary string.
	 * @var string $read_buffer
	 */
	protected $read_buffer = "";

	/**
	 * The constructor.
	 * @param integer $protocol_version
	 * @param resource $stream
	 */
	function __construct($protocol_version = -1, $stream = null)
	{
		$this->stream = $stream;
		$this->protocol_version = $protocol_version;
	}

	/**
	 * Returns the protocol version that is used for this connection.
	 * @return string
	 */
	function getProtocolVersion()
	{
		return $this->protocol_version;
	}

	/**
	 * Returns the state of the connection.
	 * @return integer
	 */
	function getState()
	{
		return $this->state;
	}

	/**
	 * Returns the compression threshold of the connection.
	 * @return integer
	 */
	function getCompressionThreshold()
	{
		return $this->compression_threshold;
	}

	/**
	 * Returns whether the stream is (still) open.
	 * @return boolean
	 */
	function isOpen()
	{
		return $this->stream != null && @feof($this->stream) === false;
	}

	/**
	 * Closes the stream.
	 * @return void
	 */
	function close()
	{
		if($this->stream != null)
		{
			@fclose($this->stream);
			$this->stream = null;
		}
	}

	/**
	 * Adds a byte to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeByte($value)
	{
		$this->write_buffer .= pack("c", $value);
		return $this;
	}

	/**
	 * Adds a boolean to the write buffer.
	 * @param boolean $value
	 * @return Connection this
	 */
	function writeBoolean($value)
	{
		$this->write_buffer .= pack("c", ($value ? 1 : 0));
		return $this;
	}

	/**
	 * Adds a VarInt to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeVarInt($value)
	{
		$this->write_buffer .= Utils::intToVarInt($value);
		return $this;
	}

	/**
	 * Adds a string to the write buffer.
	 * @param string $value
	 * @return Connection $this
	 */
	function writeString($value)
	{
		$this->write_buffer .= Utils::intToVarInt(strlen($value)).$value;
		return $this;
	}

	/**
	 * Adds a short to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeShort($value)
	{
		$this->write_buffer .= pack("n", $value);
		return $this;
	}

	/**
	 * Adds a float to the write buffer.
	 * @param float $value
	 * @return Connection $this
	 */
	function writeFloat($value)
	{
		$this->write_buffer .= pack("G", $value);
		return $this;
	}

	/**
	 * Adds an integer to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeInt($value)
	{
		$this->write_buffer .= pack("N", $value);
		return $this;
	}

	/**
	 * Adds a long to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeLong($value)
	{
		$this->write_buffer .= pack("J", $value);
		return $this;
	}

	/**
	 * Adds a double to the write buffer.
	 * @param float $value
	 * @return Connection $this
	 */
	function writeDouble($value)
	{
		$this->write_buffer .= pack("E", $value);
		return $this;
	}

	/**
	 * Adds a position to the write buffer.
	 * @param integer $x
	 * @param integer $y
	 * @param integer $z
	 * @return Connection $this
	 */
	function writePosition($x, $y, $z)
	{
		$this->writeLong((($x & 0x3FFFFFF) << 38) | (($y & 0xFFF) << 26) | ($z & 0x3FFFFFF));
		return $this;
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 * @param string $name The name of the new packet. For a list of packet names, check the source code of Packet.
	 * @return Connection $this
	 */
	function startPacket($name)
	{
		$this->write_buffer = Utils::intToVarInt(Packet::getId($name, $this->protocol_version));
		return $this;
	}

	/**
	 * Returns the contents of the write buffer.
	 * @return string
	 * @see Connection::getAndClearWriteBuffer()
	 * @see Connection::clearWriteBuffer()
	 */
	function getWriteBuffer()
	{
		return $this->write_buffer;
	}

	/**
	 * Returns and clears the contents of the write buffer.
	 * @return string
	 * @see Connection::getWriteBuffer()
	 * @see Connection::clearWriteBuffer()
	 */
	function getAndClearWriteBuffer()
	{
		$write_buffer = $this->write_buffer;
		$this->write_buffer = "";
		return $write_buffer;
	}

	/**
	 * Clears the contents of the write buffer.
	 * @return Connection $this
	 */
	function clearWriteBuffer()
	{
		$this->write_buffer = "";
		return $this;
	}

	/**
	 * Sends the contents of the write buffer over the stream and clears the write buffer or does nothing if there is no stream.
	 * @return Connection $this
	 */
	function send()
	{
		if($this->stream != null)
		{
			$length = strlen($this->write_buffer);
			if($this->compression_threshold > -1)
			{
				if($length >= $this->compression_threshold)
				{
					$compressed = gzcompress($this->write_buffer, 1);
					$compressed_length_varint = Utils::intToVarInt(strlen($compressed));
					$length += strlen($compressed_length_varint);
					fwrite($this->stream, Utils::intToVarInt($length).$compressed_length_varint.$compressed);
				}
				else
				{
					fwrite($this->stream, Utils::intToVarInt($length + 1)."\x00".$this->write_buffer);
				}
			}
			else
			{
				fwrite($this->stream, Utils::intToVarInt($length).$this->write_buffer);
			}
			$this->write_buffer = "";
		}
		return $this;
	}

	/**
	 * Reads a new packet into the read buffer.
	 * @param boolean $forcefully When true, this function will wait until a packet is received and buffered. When false, it will not wait. When a packet is on the line, it will be received and buffered, regardless of this parameter.
	 * @throws Exception When the packet length or packet id VarInt is too big.
	 * @return mixed Boolean false if `$forcefully` is `false` and there is no packet. Otherwise, packet id as an integer.
	 * @see Packet::clientboundPacketIdToName()
	 * @see Packet::serverboundPacketIdToName()
	 */
	function readPacket($forcefully = true)
	{
		$length = 0;
		$read = 0;
		do
		{
			$byte = @fgetc($this->stream);
			if($byte === false)
			{
				if(!$forcefully && $read == 0)
				{
					return false;
				}
				while($byte === false)
				{
					$byte = @fgetc($this->stream);
				}
			}
			$byte = ord($byte);
			$length |= (($byte & 0x7F) << ($read++ * 7));
			if($read > 5)
			{
				throw new \Phpcraft\Exception("VarInt is too big");
			}
			if(($byte & 0x80) != 128)
			{
				break;
			}
		}
		while(true);
		$this->read_buffer = fread($this->stream, $length);
		while(strlen($this->read_buffer) < $length)
		{
			$this->read_buffer .= fread($this->stream, $length - strlen($this->read_buffer));
		}
		if($this->compression_threshold > -1)
		{
			$uncompressed_length = $this->readVarInt();
			if($uncompressed_length > 0)
			{
				$this->read_buffer = gzuncompress($this->read_buffer, $uncompressed_length);
			}
		}
		return $this->readVarInt();
	}

	/**
	 * Reads an integer encoded as a VarInt from the read buffer.
	 * @throws Exception When there are not enough bytes to read a VarInt or the VarInt is too big.
	 * @return integer
	 */
	function readVarInt()
	{
		$value = 0;
		$read = 0;
		do
		{
			if(strlen($this->read_buffer) == 0)
			{
				throw new \Phpcraft\Exception("Not enough bytes to read VarInt\n");
			}
			$byte = ord(substr($this->read_buffer, 0, 1));
			$this->read_buffer = substr($this->read_buffer, 1);
			$value |= (($byte & 0x7F) << ($read++ * 7));
			if($read > 5)
			{
				throw new \Phpcraft\Exception("VarInt is too big\n");
			}
			if(($byte & 0x80) != 128)
			{
				break;
			}
		}
		while(true);
		return $value;
	}

	/**
	 * Reads a string from the read buffer.
	 * @param integer $maxLength
	 * @throws Exception When there are not enough bytes to read a string or the string exceeds `$maxLength`.
	 * @return string
	 */
	function readString($maxLength = 32767)
	{
		$length = $this->readVarInt();
		if($length == 0)
		{
			return "";
		}
		if($length > (($maxLength * 4) + 3))
		{
			throw new \Phpcraft\Exception("String length {$length} exceeds maximum of ".(($maxLength * 4) + 3));
		}
		if($length > strlen($this->read_buffer))
		{
			throw new \Phpcraft\Exception("Not enough bytes to read string with length {$length}");
		}
		$str = substr($this->read_buffer, 0, $length);
		$this->read_buffer = substr($this->read_buffer, $length);
		return $str;
	}

	/**
	 * Reads a byte from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a byte.
	 * @return integer
	 */
	function readByte($signed = false)
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read byte");
		}
		$byte = unpack("cbyte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		if($signed && $byte >= 0x80)
		{
			return ((($byte ^ 0xFF) + 1) * -1);
		}
		return $byte;
	}

	/**
	 * Reads a boolean from the read buffer.
	 * @throws Exception When there are not enough bytes to read a boolean.
	 * @return boolean
	 */
	function readBoolean()
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read boolean");
		}
		$byte = unpack("cbyte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		return $byte != 0;
	}

	/**
	 * Reads a short from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a short.
	 * @return integer
	 */
	function readShort($signed = true)
	{
		if(strlen($this->read_buffer) < 2)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read short");
		}
		$short = unpack("nshort", substr($this->read_buffer, 0, 2))["short"];
		$this->read_buffer = substr($this->read_buffer, 2);
		if($signed && $short >= 0x8000)
		{
			return ((($short ^ 0xFFFF) + 1) * -1);
		}
		return $short;
	}

	/**
	 * Reads an integer from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read an integer.
	 * @return integer
	 */
	function readInt($signed = true)
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read int");
		}
		$int = unpack("Nint", substr($this->read_buffer, 0, 4))["int"];
		$this->read_buffer = substr($this->read_buffer, 4);
		if($signed && $int >= 0x80000000)
		{
			return ((($int ^ 0xFFFFFFFF) + 1) * -1);
		}
		return $int;
	}

	/**
	 * Reads a long from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a long.
	 * @return integer
	 */
	function readLong($signed = true)
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read long");
		}
		$long = unpack("Jlong", substr($this->read_buffer, 0, 8))["long"];
		$this->read_buffer = substr($this->read_buffer, 8);
		if($signed && $long >= 0x8000000000000000)
		{
			return ((($long ^ 0xFFFFFFFFFFFFFFFF) + 1) * -1);
		}
		return $long;
	}

	/**
	 * Reads a position from the read buffer.
	 * @return array An array containing x, y, and z of the position.
	 */
	function readPosition()
	{
		$val = readLong(false);
		$x = $val >> 38;
		$y = ($val >> 26) & 0xFFF;
		$z = $val << 38 >> 38;
		return [$x, $y, $z];
	}

	/**
	 * Reads a float from the read buffer.
	 * @return float
	 */
	function readFloat()
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read float");
		}
		$float = unpack("Gfloat", substr($this->read_buffer, 0, 4))["float"];
		$this->read_buffer = substr($this->read_buffer, 4);
		return $float;
	}

	/**
	 * Reads a double from the read buffer.
	 * @return float
	 */
	function readDouble()
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read double");
		}
		$double = unpack("Edouble", substr($this->read_buffer, 0, 8))["double"];
		$this->read_buffer = substr($this->read_buffer, 8);
		return $double;
	}

	/**
	 * Reads a binary string consisting of 16 bytes.
	 * @return string
	 */
	function readUUIDBytes()
	{
		if(strlen($this->read_buffer) < 16)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read UUID");
		}
		$uuid = substr($this->read_buffer, 0, 16);
		$this->read_buffer = substr($this->read_buffer, 16);
		return $uuid;
	}

	/**
	 * Ignores the given amount of bytes.
	 * @param integer $bytes
	 * @return Connection $this
	 */
	function ignoreBytes($bytes)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new \Phpcraft\Exception("There are less than {$bytes} bytes");
		}
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $this;
	}
}

/** A client-to-server connection. */
class ServerConnection extends Connection
{
	/**
	 * The constructor.
	 * @param string $server_name
	 * @param integer $server_port
	 * @param integer $next_state 1 stands for status and 2 for login to play.
	 * @param integer $protocol_version
	 */
	function __construct($server_name, $server_port, $next_state, $protocol_version = 404)
	{
		if(!($stream = fsockopen($server_name, $server_port, $errno, $errstr, 10)))
		{
			throw new \Phpcraft\Exception($errstr);
		}
		stream_set_blocking($stream, false);
		parent::__construct($protocol_version, $stream);
		$this->writeVarInt(0x00);
		$this->writeVarInt($protocol_version);
		$this->writeString($server_name);
		$this->writeShort($server_port);
		$this->writeVarInt($this->state = $next_state);
		$this->send();
	}
}

/** A client-to-server connection with the intention of getting the server's status. */
class ServerStatusConnection extends ServerConnection
{
	/**
	 * The constructor.
	 * After this, you should call ServerStatusConnection::getStatus().
	 * @param string $server_name
	 * @param integer $server_port
	 */
	function __construct($server_name, $server_port = 25565)
	{
		parent::__construct($server_name, $server_port, 1);
	}

	/**
	 * Returns the server list ping as multi-dimensional array with the addition of the "ping" value which is in seconds and closes the connection.
	 * Here's an example:
	 * <pre>[
	 *   "version" => [
	 *     "name" => "1.12.2",
	 *     "protocol" => 340
	 *   ],
	 *   "players" => [
	 *     "max" => 20,
	 *     "online" => 1,
	 *     "sample" => [
	 *       [
	 *         "name" => "timmyRS",
	 *         "id" => "4566e69f-c907-48ee-8d71-d7ba5aa00d20"
	 *       ]
	 *     ]
	 *   ],
	 *   "description" => [
	 *     "text" => "A Minecraft Server"
	 *   ],
	 *   "favicon" => "data:image/png;base64,&lt;data&gt;",
	 *   "ping" => 0.068003177642822
	 * ]</pre>
	 *
	 * Note that a server might not present all of these values, so always check with `isset` first.
	 *
	 * Also, the `description` is a chat object, so you can pass it to Utils::chatToANSIText().
	 * @return array
	 */
	function getStatus()
	{
		// TODO: Legacy list ping
		$start = microtime(true);
		$this->writeVarInt(0x00);
		$this->send();
		if($this->readPacket() != 0x00)
		{
			throw new \Phpcraft\Exception("Invalid response to status request: {$id} ".bin2hex($this->read_buffer)."\n");
		}
		$json = json_decode($this->readString(), true);
		$json["ping"] = microtime(true) - $start;
		$this->close();
		return $json;
	}
}

/** A client-to-server connection with the intention of playing on it. */
class ServerPlayConnection extends ServerConnection
{
	private $username;
	private $uuid;

	/**
	 * The constructor.
	 * After this, you should call ServerPlayConnection::login(), even when joining an offline mode server.
	 * @param integer $protocol_version
	 * @param string $server_name
	 * @param integer $server_port
	 */
	function __construct($protocol_version, $server_name, $server_port = 25565)
	{
		parent::__construct($server_name, $server_port, 2, $protocol_version);
	}

	/**
	 * Returns our name on the server.
	 * The return value will be equal to the return value of Account::getUsername() of the account passed to ServerPlayConnection::login().
	 * @return string
	 */
	function getUsername()
	{
		return $this->username;
	}

	/**
	 * Returns our UUID with hypens.
	 * @return string
	 */
	function getUUID()
	{
		return $this->uuid;
	}

	/**
	 * Logs in to the server using the given account.
	 * This has to be called even when joining an offline mode server.
	 * @param Account $account
	 * @param array $translations The translations array so translated messages look proper.
	 * @throws Exception When the server responds unexpectedly.
	 * @return string Error message. Empty on success.
	 */
	function login($account, $translations = null)
	{
		$this->writeVarInt(0x00);
		$this->writeString($account->getUsername());
		$this->send();
		do
		{
			$id = $this->readPacket();
			if($id == 0x04) // Login Plugin Request
			{
				$this->writeVarInt(0x02); // Login Plugin Response
				$this->writeVarInt($this->readVarInt());
				$this->writeBoolean(false);
				$this->send();
			}
			else if($id == 0x03) // Set Compression
			{
				$this->compression_threshold = $this->readVarInt();
			}
			else if($id == 0x02) // Login Success
			{
				$this->uuid = $this->readString(36);
				$name = $this->readString(16);
				if($account->getUsername() != $name)
				{
					return "Server did not accept our username and would rather call us '{$name}'.";
				}
				$this->username = $name;
				$this->state = 3;
				return "";
			}
			else if($id == 0x01) // Encryption Request
			{
				if(!$account->isOnline())
				{
					return "The server is in online mode.";
				}
				$server_id = $this->readString(20);
				$public_key = $this->readString();
				$verify_token = $this->readString();
				$shared_secret = "";
				for($i = 0; $i < 16; $i++)
				{
					$shared_secret .= chr(rand(0, 255));
				}
				if(Utils::httpPOST("https://sessionserver.mojang.com/session/minecraft/join", [
					"accessToken" => $account->getAccessToken(),
					"selectedProfile" => $account->getProfileId(),
					"serverId" => Utils::sha1($server_id.$shared_secret.$public_key)
				]) === false)
				{
					return "The session server is down for maintenance.";
				}
				$public_key = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".base64_encode($public_key)."\n-----END PUBLIC KEY-----");
				$this->writeVarInt(0x01); // Encryption Response
				$crypted = "";
				openssl_public_encrypt($shared_secret, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				openssl_public_encrypt($verify_token, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				$this->send();
				$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
				stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
				stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
			}
			else if($id == 0x00) // Disconnect
			{
				return Utils::chatToANSIText(json_decode($this->readString(), true), $translations);
			}
			else
			{
				throw new \Phpcraft\Exception("Unexpected response: {$id} ".bin2hex($this->read_buffer)."\n");
			}
		}
		while(true);
	}
}

/** A server-to-client connection. */
class ClientConnection extends Connection
{
	/**
	 * The constructor.
	 * The handshake will be read and the connection will be closed when an error occurs.
	 * After this, you should check Connection::isOpen() and then Connection::getState() to see if the client wants to get the status (1) or login to play (2).
	 * @param resource $stream
	 */
	function __construct($stream)
	{
		parent::__construct(-1, $stream);
		stream_set_timeout($this->stream, 0, 10000);
		stream_set_blocking($this->stream, true);
		if($this->readPacket() === 0x00)
		{
			$this->protocol_version = $this->readVarInt();
			$this->readString(); // hostname/ip
			$this->ignoreBytes(2); // port
			$this->state = $this->readVarInt();
			if($this->state == 1 || $this->state == 2)
			{
				if($this->state != 2 || Utils::isProtocolVersionSupported($this->protocol_version))
				{
					stream_set_timeout($this->stream, ini_get("default_socket_timeout"));
					stream_set_blocking($this->stream, false);
				}
				else
				{
					$this->writeVarInt(0x00);
					$this->writeString('{"text":"You\'re not using a compatible version."}');
					$this->send();
					$this->close();
				}
			}
			else
			{
				$this->close();
			}
		}
		else
		{
			$this->close();
		}
	}

	/**
	 * Sends an Encryption Request Packet.
	 * @param string $private_key Your OpenSSL private key resource.
	 * @return ClientConnection $this
	 */
	function sendEncryptionRequest($private_key)
	{
		if($this->state == 2)
		{
			$this->writeVarInt(0x01);
			$this->writeString(""); // Server ID
			$this->writeString(base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24)))); // Public Key
			$this->writeString("MATE"); // Verify Token
			$this->send();
		}
		return $this;
	}

	/**
	 * Reads an encryption response packet's data, authenticates with Mojang, sets the compression threshold, and finishes login.
	 * If there is an error, the client is disconnected and false is returned.
	 * On success, an array looking like this is returned:
	 * <pre>[
	 *   "id" => "11111111222233334444555555555555",
	 *   "name" => "Notch",
	 *   "properties" => [
	 *     [
	 *       "name" => "textures",
	 *       "value" => "&lt;base64 string&gt;",
	 *       "signature" => "&lt;base64 string; signed data using Yggdrasil's private key&gt;"
	 *     ]
	 *   ]
	 * ]</pre>
	 * After this, you should call ClientConnection::finishLogin().
	 * @param string $name The name the client presented in the Login Start packet.
	 * @param string $private_key Your OpenSSL private key resource.
	 * @return mixed
	 */
	function handleEncryptionResponse($name, $private_key)
	{
		openssl_private_decrypt($this->readString(), $shared_secret, $private_key, OPENSSL_PKCS1_PADDING);
		openssl_private_decrypt($this->readString(), $verify_token, $private_key, OPENSSL_PKCS1_PADDING);
		if($verify_token !== "MATE")
		{
			$this->close();
			return false;
		}
		$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
		stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
		stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
		$json = @json_decode(@file_get_contents("https://sessionserver.mojang.com/session/minecraft/hasJoined?username={$name}&serverId=".Utils::sha1($shared_secret.base64_decode(trim(substr(openssl_pkey_get_details($private_key)["key"], 26, -24))))), true);
		if(!$json || empty($json["id"]) || empty($json["name"]) || $json["name"] != $name)
		{
			$this->writeVarInt(0x00);
			$this->writeString('{"text":"Failed to authenticate against session server."}');
			$this->send();
			$this->close();
			return false;
		}
		return $json;
	}

	/**
	 * Sets the compression threshold and finishes the login.
	 * @param string $uuid The client's UUID as a string with hypens.
	 * @param string $name The name the client presented in the Login Start packet.
	 * @param integer $compression_threshold Use -1 to disable compression.
	 * @return ClientConnection $this
	 * @see Utils::generateUUIDv4()
	 * @see Utils::addHypensToUUID()
	 */
	function finishLogin($uuid, $name, $compression_threshold = 256)
	{
		if($this->state == 2)
		{
			if($compression_threshold > -1 || $this->protocol_version < 48)
			{
				$this->writeVarInt(0x03);
				$this->writeVarInt($compression_threshold);
				$this->send();
			}
			$this->compression_threshold = $compression_threshold;
			$this->writeVarInt(0x02);
			$this->writeString($uuid);
			$this->writeString($name);
			$this->send();
			$this->state = 3;
		}
		return $this;
	}

	/**
	 * Disconnects the client with a reason.
	 * @param array $reason The reason of the disconnect; chat object.
	 * @return void
	 */
	function disconnect($reason = [])
	{
		if($reason)
		{
			if($this->state == 2)
			{

				$this->writeVarInt(0x00);
				$this->writeString(json_encode($reason));
				$this->send();
			}
			else
			{
				(new DisconnectPacket($reason))->send($this);
			}
		}
		$this->close();
	}
}

/**
 * A Packet.
 * Look at the source code of this class for a list of packet names.
 */
abstract class Packet
{
	private static function clientboundPackets()
	{
		return [
			"spawn_player" => [0x05, 0x05, 0x05, 0x05, 0x05, 0x05, 0x0C],
			"chat_message" => [0x0E, 0x0F, 0x0F, 0x0F, 0x0F, 0x0F, 0x02],
			"plugin_message" => [0x19, 0x18, 0x18, 0x18, 0x18, 0x18, 0x3F],
			"disconnect" => [0x1B, 0x1A, 0x1A, 0x1A, 0x1A, 0x1A, 0x40],
			"open_window" => [0x14, 0x13, 0x13, 0x13, 0x13, 0x13, 0x2D],
			"change_game_state" => [0x20, 0x1E, 0x1E, 0x1E, 0x1E, 0x1E, 0x2B],
			"keep_alive_request" => [0x21, 0x1F, 0x1F, 0x1F, 0x1F, 0x1F, 0x00],
			"join_game" => [0x25, 0x23, 0x23, 0x23, 0x23, 0x23, 0x01],
			"entity_relative_move" => [0x28, 0x26, 0x26, 0x25, 0x25, 0x25, 0x15],
			"entity_look_and_relative_move" => [0x29, 0x27, 0x27, 0x26, 0x26, 0x26, 0x17],
			"entity_look" => [0x2A, 0x28, 0x28, 0x27, 0x27, 0x27, 0x16],
			"player_list_item" => [0x30, 0x2E, 0x2D, 0x2D, 0x2D, 0x2D, 0x38],
			"teleport" => [0x32, 0x2F, 0x2E, 0x2E, 0x2E, 0x2E, 0x08],
			"destroy_entites" => [0x35, 0x32, 0x31, 0x30, 0x30, 0x30, 0x13],
			"respawn" => [0x38, 0x35, 0x34, 0x33, 0x33, 0x33, 0x07],
			"update_health" => [0x44, 0x41, 0x40, 0x3E, 0x3E, 0x3E, 0x06],
			"spawn_position" => [0x49, 0x46, 0x45, 0x43, 0x43, 0x43, 0x05],
			"time_update" => [0x4A, 0x47, 0x46, 0x44, 0x44, 0x44, 0x03],
			"player_list_header_and_footer" => [0x4E, 0x4A, 0x49, 0x47, 0x47, 0x48, 0x47],
			"entity_teleport" => [0x50, 0x4C, 0x4B, 0x49, 0x4A, 0x18]
		];
	}

	private static function serverboundPackets()
	{
		return [
			"teleport_confirm" => [0x00, 0x00, 0x00, 0x00, 0x00, 0x00, -1],
			"send_chat_message" => [0x02, 0x02, 0x03, 0x02, 0x02, 0x02, 0x01],
			"client_status" => [0x03, 0x03, 0x04, 0x03, 0x03, 0x03, 0x16],
			"client_settings" => [0x04, 0x04, 0x05, 0x04, 0x04, 0x04, 0x15],
			"close_window" => [0x09, 0x08, 0x09, 0x08, 0x08, 0x08, 0x0D],
			"send_plugin_message" => [0x0A, 0x09, 0x0A, 0x09, 0x09, 0x09, 0x17],
			"keep_alive_response" => [0x0E, 0x0B, 0x0C, 0x0B, 0x0B, 0x0B, 0x00],
			"player" => [0x0F, 0x0C, 0x0D, 0x0F, 0x0F, 0x0F, 0x03],
			"player_position" => [0x10, 0x0D, 0x0E, 0x0C, 0x0C, 0x0C, 0x04],
			"player_position_and_look" => [0x11, 0x0E, 0x0F, 0x0D, 0x0D, 0x0D, 0x06],
			"player_look" => [0x12, 0x0F, 0x10, 0x0E, 0x0E, 0x0E, 0x05],
			"held_item_change" => [0x21, 0x1A, 0x1A, 0x17, 0x17, 0x17, 0x09],
			"animation" => [0x27, 0x1D, 0x1D, 0x1A, 0x1A, 0x1A, 0x0A],
			"player_block_placement" => [0x29, 0x1F, 0x1F, 0x1C, 0x1C, 0x1C, 0x08],
			"use_item" => [0x2A, 0x20, 0x20, 0x1D, 0x1D, 0x1D, -1],
		];
	}

	/**
	 * The name of the packet.
	 * @var string $name
	 */
	protected $name;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 */
	protected function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the name of the packet.
	 * @return string
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the id of the packet name for the given protocol version.
	 * @param string $name The name of the packet.
	 * @param integer $protocol_version
	 * @return integer -1 if not applicable for protocol version or null if the packet is unknown.
	 */
	static function getId($name, $protocol_version)
	{
		$clientbound_packet_ids = Packet::clientboundPackets();
		$serverbound_packet_ids = Packet::serverboundPackets();
		if($protocol_version >= 393)
		{
			return isset($clientbound_packet_ids[$name][0]) ? $clientbound_packet_ids[$name][0] : (isset($serverbound_packet_ids[$name][0]) ? $serverbound_packet_ids[$name][0] : null);
		}
		else if($protocol_version >= 336)
		{
			return isset($clientbound_packet_ids[$name][1]) ? $clientbound_packet_ids[$name][1] : (isset($serverbound_packet_ids[$name][1]) ? $serverbound_packet_ids[$name][1] : null);
		}
		else if($protocol_version >= 328)
		{
			return isset($clientbound_packet_ids[$name][2]) ? $clientbound_packet_ids[$name][2] : (isset($serverbound_packet_ids[$name][2]) ? $serverbound_packet_ids[$name][2] : null);
		}
		else if($protocol_version >= 314)
		{
			return isset($clientbound_packet_ids[$name][3]) ? $clientbound_packet_ids[$name][3] : (isset($serverbound_packet_ids[$name][3]) ? $serverbound_packet_ids[$name][3] : null);
		}
		else if($protocol_version >= 110)
		{
			return isset($clientbound_packet_ids[$name][4]) ? $clientbound_packet_ids[$name][4] : (isset($serverbound_packet_ids[$name][4]) ? $serverbound_packet_ids[$name][4] : null);
		}
		else if($protocol_version >= 107)
		{
			return isset($clientbound_packet_ids[$name][5]) ? $clientbound_packet_ids[$name][5] : (isset($serverbound_packet_ids[$name][5]) ? $serverbound_packet_ids[$name][5] : null);
		}
		return isset($clientbound_packet_ids[$name][6]) ? $clientbound_packet_ids[$name][6] : (isset($serverbound_packet_ids[$name][6]) ? $serverbound_packet_ids[$name][6] : null);
	}

	/**
	 * Returns the id of this packet for the given protocol version.
	 * @param integer $protocol_version
	 * @return integer -1 if not applicable for protocol version or null if the packet is unknown.
	 */
	function idFor($protocol_version)
	{
		return Packet::getId($this->name, $protocol_version);
	}

	private static function extractPacketNameFromList($list, $id, $protocol_version)
	{
		foreach($list as $n => $v)
		{
			if($protocol_version >= 393)
			{
				if($v[0] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 336)
			{
				if($v[1] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 328)
			{
				if($v[2] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 314)
			{
				if($v[3] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 110)
			{
				if($v[4] == $id)
				{
					return $n;
				}
			}
			else if($protocol_version >= 107)
			{
				if($v[5] == $id)
				{
					return $n;
				}
			}
			else if($v[6] == $id)
			{
				return $n;
			}
		}
		return null;
	}

	/**
	 * Converts a clientbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 */
	static function clientboundPacketIdToName($id, $protocol_version)
	{
		return Packet::extractPacketNameFromList(Packet::clientboundPackets(), $id, $protocol_version);
	}

	/**
	 * Converts a serverbound packet ID to its name as a string or null if unknown.
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return string
	 */
	static function serverboundPacketIdToName($id, $protocol_version)
	{
		return Packet::extractPacketNameFromList(Packet::serverboundPackets(), $id, $protocol_version);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	abstract static function read($con);

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	abstract function send($con);
}

/** The template for the keep alive request and response packets. */
abstract class KeepAlivePacket extends Packet
{
	/**
	 * The identifier of the keep alive packet.
	 * @var integer $keepAliveId
	 */
	protected $keepAliveId;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	protected function __construct($name, $keepAliveId)
	{
		parent::__construct($name);
		if($keepAliveId == null)
		{
			$this->keepAliveId = time();
		}
		else
		{
			$this->keepAliveId = $keepAliveId;
		}
	}

	/**
	 * Returns the identifier of the keep alive packet.
	 * @return integer
	 */
	function getKeepAliveId()
	{
		return $this->keepAliveId;
	}

	/**
	 * Called by children when Packet::read() is being called.
	 * @param Connection $con
	 */
	protected function _read($con)
	{
		if($con->getProtocolVersion() >= 339)
		{
			$this->keepAliveId = $con->readLong();
		}
		else
		{
			$this->keepAliveId = $con->readVarInt();
		}
		return $this;
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send($con)
	{
		$con->startPacket($this->name);
		if($con->getProtocolVersion() >= 339)
		{
			$con->writeLong($this->keepAliveId);
		}
		else
		{
			$con->writeVarInt($this->keepAliveId);
		}
		$con->send();
	}
}
/** Sent by the server to the client to make sure it's still connected. */
class KeepAliveRequestPacket extends KeepAlivePacket
{
	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_request", $keepAliveId);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read($con)
	{
		return (new KeepAliveRequestPacket())->_read($con);
	}

	/**
	 * Generates the response packet which the client should send.
	 * @return KeepAliveResponsePacket
	 */
	function getResponse()
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}
}

/** Sent by the client to the server in response to KeepAliveRequestPacket. */
class KeepAliveResponsePacket extends KeepAlivePacket
{
	/**
	 * The constructor.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_response", $keepAliveId);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read($con)
	{
		return (new KeepAliveResponsePacket())->_read($con);
	}
}

/** A packet that contains a chat object. */
abstract class ChatPacket extends Packet
{
	private $message;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 * @param object $message The chat object that is being sent.
	 */
	protected function __construct($name, $message)
	{
		parent::__construct($name);
		$this->message = $message;
	}

	/**
	 * Returns the chat object that is being sent.
	 * @return string
	 */
	function getMessage()
	{
		return $this->message;
	}

	/**
	 * Returns the message that is being sent as text with ANSI escape codes so it will be colorful in the console, as well.
	 * @param array $translations The translations array so translated messages look proper.
	 * @see Utils::chatToANSIText()
	 */
	function getMessageAsANSIText($translations = null)
	{
		return Utils::chatToANSIText($this->message, $translations);
	}

	/**
	 * Called by children when Packet::read() is being called.
	 * @param Connection $con
	 */
	protected function _read($con)
	{
		$this->message = json_decode($con->readString(), true);
		return $this;
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send($con)
	{
		$con->startPacket($this->name);
		$con->writeString(json_encode($this->message));
		$con->send();
	}
}

/** Sent by the server to the client when it's closing the connection with a chat object as reason. */
class DisconnectPacket extends ChatPacket
{
	/**
	 * The constructor.
	 * @param string $message The disconnect reason; chat object.
	 */
	function __construct($message = [])
	{
		parent::__construct("disconnect", $message);
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read($con)
	{
		return (new DisconnectPacket())->_read($con);
	}
}

/** Sent by the server to the client when there's a new message. */
class ChatMessagePacket extends ChatPacket
{
	private $position = 0;

	/**
	 * The constructor.
	 * @param array $message The chat object that is being sent.
	 * @param integer $position 0 = player message, 1 = system message, 2 = game info (above hotbar).
	 */
	function __construct($message = [], $position = 1)
	{
		parent::__construct("chat_message", $message);
		$this->position = $position;
	}

	/**
	 * Returns the position of the message.
	 * @return integer 0 for player message, 1 for system message, or 2 for game info (above hotbar).
	 */
	function getPosition()
	{
		return $this->position;
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read($con)
	{
		return new ChatMessagePacket(json_decode($con->readString(), true), $con->readByte());
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send($con)
	{
		$con->startPacket($this->name);
		$con->writeString(json_encode($this->message));
		$con->writeByte($position);
		$con->send();
	}
}

/** Sent by the client to the server when it wants to send a message or execute a command. */
class SendChatMessagePacket extends Packet
{
	private $message;

	/**
	 * The constructor.
	 * @param string $message The message you want to send; not a chat object.
	 */
	function __construct($message)
	{
		parent::__construct("send_chat_message");
		$this->message = $message;
	}

	/**
	 * Initializes the packet via the Connection.
	 * Note that you should already have used Connection::readPacket() and determined that the packet you are initializing has actually been sent.
	 * @param Connection $con
	 */
	static function read($con)
	{
		return new SendMessagePacket($this->readString(256));
	}

	/**
	 * Sends the packet over the given Connection.
	 * There is different behaviour if the Connection object was initialized without a stream. See Connection::send() for details.
	 * @param Connection $con
	 */
	function send($con)
	{
		$con->startPacket($this->name);
		$con->writeString($this->message);
		$con->send();
	}
}
