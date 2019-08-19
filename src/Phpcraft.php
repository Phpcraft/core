<?php
namespace Phpcraft;
use hellsh\UUID;
use Phpcraft\Exception\IOException;
abstract class Phpcraft
{
	private static $json_cache = [];

	/**
	 * Returns the contents of Minecraft's launcher_profiles.json with some values being set if they are unset.
	 *
	 * @return array
	 * @see Phpcraft::getProfilesFile()
	 * @see Phpcraft::saveProfiles()
	 */
	static function getProfiles()
	{
		$profiles_file = Phpcraft::getProfilesFile();
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
			$profiles["clientToken"] = UUID::v4()
										   ->__toString();
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
	 * Returns the path of Minecraft's launcher_profiles.json.
	 *
	 * @return string
	 */
	static function getProfilesFile()
	{
		return Phpcraft::getMinecraftFolder()."/launcher_profiles.json";
	}

	/**
	 * Returns the path of the .minecraft folder without a folder seperator at the end.
	 *
	 * @return string
	 */
	static function getMinecraftFolder()
	{
		if(Phpcraft::isWindows())
		{
			$minecraft_folder = getenv("APPDATA")."\\.minecraft";
		}
		else if(stristr(PHP_OS, "LINUX"))
		{
			$minecraft_folder = getenv("HOME")."/.minecraft";
		}
		else if(stristr(PHP_OS, "DAR"))
		{
			$minecraft_folder = getenv("HOME")."/Library/Application Support/minecraft";
		}
		else
		{
			$minecraft_folder = __DIR__."/.minecraft";
		}
		if(!file_exists($minecraft_folder) || !is_dir($minecraft_folder))
		{
			mkdir($minecraft_folder);
		}
		return $minecraft_folder;
	}

	/**
	 * Returns true if the code is running on a Windows machine.
	 *
	 * @return boolean
	 */
	static function isWindows()
	{
		return defined("PHP_WINDOWS_VERSION_MAJOR");
	}

	/**
	 * Saves the profiles array into Minecraft's launcher_profiles.json.
	 *
	 * @param array $profiles
	 */
	static function saveProfiles(array $profiles)
	{
		file_put_contents(Phpcraft::getProfilesFile(), json_encode($profiles, JSON_PRETTY_PRINT));
	}

	/**
	 * Returns the contents of a JSON file as associative array with additional memory and disk caching levels.
	 *
	 * @param string $url The URL of the resource.
	 * @param integer $caching_duration How long the resource should be kept in the cache, in seconds. (Default: 31 days)
	 * @return array
	 * @see getCachableResource
	 * @see maintainCache
	 */
	static function getCachableJson(string $url, int $caching_duration = 2678400)
	{
		if(!isset(self::$json_cache[$url]))
		{
			self::$json_cache[$url] = json_decode(self::getCachableResource($url, $caching_duration), true);
		}
		return self::$json_cache[$url];
	}

	/**
	 * Returns the contents of a resource with an additional disk caching level.
	 *
	 * @param string $url The URL of the resource.
	 * @param integer $caching_duration How long the resource should be kept in the cache, in seconds. (Default: 1 day)
	 * @return string
	 * @see getCachableJson
	 * @see maintainCache
	 */
	static function getCachableResource(string $url, int $caching_duration = 86400)
	{
		$cache = [];
		if(file_exists(__DIR__."/.cache"))
		{
			if(filemtime(__DIR__."/.cache") < time() - 86400)
			{
				self::maintainCache();
			}
			if(file_exists(__DIR__."/.cache"))
			{
				$cache = json_decode(file_get_contents(__DIR__."/.cache"), true);
			}
		}
		if(empty($cache[$url]))
		{
			$cache[$url] = [
				"contents" => file_get_contents($url),
				"expiry" => time() + $caching_duration
			];
			file_put_contents(__DIR__."/.cache", json_encode($cache));
		}
		return $cache[$url]["contents"];
	}

	/**
	 * Deletes expired cache entries.
	 *
	 * @see getCachableJson
	 * @see getCachableResource
	 */
	static function maintainCache()
	{
		if(!file_exists(__DIR__."/.cache"))
		{
			return;
		}
		$cache = json_decode(file_get_contents(__DIR__."/.cache"), true);
		$time = time();
		foreach($cache as $url => $entry)
		{
			if($entry["expiry"] < $time)
			{
				unset($cache[$url]);
				if(isset(self::$json_cache[$url]))
				{
					unset(self::$json_cache[$url]);
				}
			}
		}
		if(empty($cache))
		{
			unlink(__DIR__."/.cache");
		}
		else
		{
			file_put_contents(__DIR__."/.cache", json_encode($cache));
		}
	}

	/**
	 * Downloads various resources which might be needed during runtime but are not yet in the disk cache, and populates the memory cache.
	 * This improves performance for BlockState, Item, PacketId, EntityType, and EntityMetadata::read.
	 */
	static function populateCache()
	{
		BlockState::all();
		Item::all();
		PacketId::all();
		EntityType::all()[0]->getId(353);
	}

	/**
	 * Validates an in-game name.
	 *
	 * @param string $name
	 * @return boolean True if the name is valid.
	 */
	static function validateName(string $name)
	{
		if(strlen($name) < 3 || strlen($name) > 16)
		{
			return false;
		}
		$allowed_characters = [
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
	 * Sends an HTTP POST request with a JSON payload.
	 * The response will always contain a "status" value which will be the HTTP response code, e.g. 200.
	 *
	 * @param string $url
	 * @param array $data
	 * @return array
	 */
	static function httpPOST(string $url, array $data)
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
	 *
	 * @param string $server The server address, e.g. localhost
	 * @return string The resolved address, e.g. localhost:25565
	 */
	static function resolve(string $server)
	{
		$arr = explode(":", $server);
		if(count($arr) > 1)
		{
			return Phpcraft::resolveName($arr[0], false).":".$arr[1];
		}
		return Phpcraft::resolveName($server, true);
	}

	private static function resolveName(string $server, bool $withPort = true)
	{
		if(ip2long($server) === false && $res = @dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
		{
			$i = array_rand($res);
			return Phpcraft::resolveName($res[$i]["target"], false).($withPort ? ":".$res[$i]["port"] : "");
		}
		return $server.($withPort ? ":25565" : "");
	}

	static function binaryStringToHex(string $str)
	{
		$hex_str = "";
		foreach(str_split($str) as $char)
		{
			$char = dechex(ord($char));
			if(strlen($char) != 2)
			{
				$char = "0".$char;
			}
			$hex_str .= $char." ";
		}
		return rtrim($hex_str);
	}

	/**
	 * Generates a Minecraft-style SHA1 hash.
	 * This function requires GMP to be installed, but is only needed when going online.
	 *
	 * @param string $str
	 * @return string
	 */
	static function sha1(string $str)
	{
		$gmp = gmp_import(sha1($str, true));
		if(gmp_cmp($gmp, gmp_init("0x8000000000000000000000000000000000000000")) >= 0)
		{
			$gmp = gmp_mul(gmp_add(gmp_xor($gmp, gmp_init("0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")), gmp_init(1)), gmp_init(-1));
		}
		return gmp_strval($gmp, 16);
	}

	/**
	 * Converts a chat object into text.
	 *
	 * @param array|string $chat The chat object as an array or string.
	 * @param integer $format The formatting to convert to: <ul><li>0: None (drop colors and formatting)</li><li>1: ANSI escape codes (for compatible consoles/shells)</li><li>2: Legacy paragraph (§) format</li><li>3: Even more legacy ampersand (&) format</li><li>4: HTML</li></ul>
	 * @param array $translations The translations array so translated messages look proper.
	 * @param array $parent Ignore this parameter.
	 * @return string
	 */
	static function chatToText($chat, int $format = 0, array $translations = null, array $parent = [])
	{
		if($parent === [])
		{
			if($format < 0 || $format > 4)
			{
				trigger_error("Format has to be an integer between 0 and 4. Defaulting to 0.");
				$format = 0;
			}
			if($translations == null)
			{
				$translations = [
					"chat.type.text" => "<%s> %s",
					"chat.type.announcement" => "[%s] %s",
					"multiplayer.player.joined" => "%s joined the game",
					"multiplayer.player.left" => "%s left the game"
				];
			}
		}
		if(gettype($chat) == "string")
		{
			if(strpos($chat, "§") === false)
			{
				return $chat;
			}
			$chat = Phpcraft::textToChat($chat);
		}
		$text = "";
		$closing_tags = "";
		if($format > 0)
		{
			$ansi_modifiers = [];
			if($format == 1)
			{
				$attributes = [
					"reset" => "0",
					"bold" => "1",
					"italic" => "3",
					"underlined" => "4",
					"obfuscated" => "8",
					"strikethrough" => "9"
				];
			}
			else if($format == 4)
			{
				$attributes = [
					"bold" => "b",
					"italic" => "i",
					"underlined" => 'span style="text-decoration:underline"',
					"strikethrough" => "del"
				];
			}
			else
			{
				if($parent === [])
				{
					$text = ($format == 2 ? "§" : "&")."r";
				}
				$attributes = [
					"obfuscated" => "k",
					"bold" => "l",
					"strikethrough" => "m",
					"underlined" => "n",
					"italic" => "o",
					"reset" => "r"
				];
			}
			foreach($attributes as $n => $v)
			{
				if(!isset($chat[$n]))
				{
					if(!isset($parent[$n]))
					{
						continue;
					}
					$chat[$n] = $parent[$n];
				}
				if($chat[$n] && $chat[$n] !== "false")
				{
					if($format == 1)
					{
						array_push($ansi_modifiers, $v);
					}
					else if($format == 2)
					{
						$text .= "§".$v;
					}
					else if($format == 3)
					{
						$text .= "&".$v;
					}
					else
					{
						$text .= "<{$v}>";
						$closing_tags .= "</".explode(" ", $v)[0].">";
					}
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
				if($format == 1)
				{
					$colors = [
						"black" => "30",
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
						array_push($ansi_modifiers, $colors[$chat["color"]]);
					}
					$text .= "\x1B[".join(";", $ansi_modifiers)."m";
				}
				else if($format == 4)
				{
					$colors = [
						"black" => "000",
						"dark_blue" => "0000aa",
						"dark_green" => "00aa00",
						"dark_aqua" => "00aaaa",
						"dark_red" => "aa0000",
						"dark_purple" => "aa00aa",
						"gold" => "ffaa00",
						"gray" => "aaa",
						"dark_gray" => "555",
						"blue" => "5555ff",
						"green" => "55ff55",
						"aqua" => "55ffff",
						"red" => "ff5555",
						"light_purple" => "ff55ff",
						"yellow" => "ffff55",
						"white" => "fff"
					];
					if(isset($colors[$chat["color"]]))
					{
						$text .= '<span style="color:#'.$colors[$chat["color"]].'">';
						$closing_tags .= "</span>";
					}
				}
				else if(($i = array_search($chat["color"], [
						"black",
						"dark_blue",
						"dark_green",
						"dark_aqua",
						"dark_red",
						"dark_purple",
						"gold",
						"gray",
						"dark_gray",
						"blue",
						"green",
						"aqua",
						"red",
						"light_purple",
						"yellow",
						"white"
					])) !== false)
				{
					$text .= ($format == 2 ? "§" : "&").dechex(intval($i));
				}
			}
		}
		if(isset($chat["translate"]))
		{
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
					array_push($with, Phpcraft::chatToText($extra, $format, $translations, $chat));
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
			$text .= $chat["text"];
		}
		if(isset($chat["extra"]))
		{
			foreach($chat["extra"] as $extra)
			{
				$text .= Phpcraft::chatToText($extra, $format, $translations, $chat);
			}
		}
		if($format == 4)
		{
			$text .= $closing_tags;
		}
		return $text;
	}

	/**
	 * Converts a string using § format codes into a chat object.
	 *
	 * @param string $text
	 * @param boolean $allowAmp If true, '&' will be handled like '§'.
	 * @return array
	 */
	static function textToChat(string $text, bool $allowAmp = false)
	{
		if(strpos($text, "§") === false && (!$allowAmp || strpos($text, "&") === false))
		{
			return ["text" => $text];
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
		$components = [["text" => ""]];
		$component = 0;
		$lastWasParagraph = false;
		foreach(preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY) as $c)
		{
			if($c == "§" || ($allowAmp && $c == "&"))
			{
				$lastWasParagraph = true;
			}
			else if($lastWasParagraph)
			{
				$lastWasParagraph = false;
				if($c == "r")
				{
					if($component != 0)
					{
						$components[++$component] = ["text" => ""];
					}
					continue;
				}
				if($component == 0 || $components[$component]["text"] != "")
				{
					$components[++$component] = ["text" => ""];
				}
				if($c == "k")
				{
					$components[$component]["obfuscated"] = true;
				}
				else if($c == "l")
				{
					$components[$component]["bold"] = true;
				}
				else if($c == "m")
				{
					$components[$component]["strikethrough"] = true;
				}
				else if($c == "n")
				{
					$components[$component]["underlined"] = true;
				}
				else if($c == "o")
				{
					$components[$component]["italic"] = true;
				}
				else if(isset($colors[$c]))
				{
					$components[$component]["color"] = $colors[$c];
				}
			}
			else
			{
				$components[$component]["text"] .= $c;
			}
		}
		if($components[0]["text"] == "")
		{
			unset($components[0]["text"]);
		}
		$chat = $components[0];
		if($component > 0)
		{
			if($component == 1 && !array_key_exists("text", $chat))
			{
				$chat = $components[1];
			}
			else
			{
				$chat["extra"] = array_slice($components, 1);
			}
		}
		return $chat;
	}

	/**
	 * Returns the server list ping as multi-dimensional array with the addition of the "ping" value which is in seconds. In an error case, an empty array is returned.
	 * Here's an example:
	 * <pre>[
	 *   "version" => [
	 *     "name" => "1.12.2",
	 *     "protocol" => 340
	 *   ],
	 *   "players" => [
	 *     "online" => 1,
	 *     "max" => 20,
	 *     "sample" => [
	 *       [
	 *         "name" => "timmyRS",
	 *         "id" => "e0603b59-2edc-45f7-acc7-b0cccd6656e1"
	 *       ]
	 *     ]
	 *   ],
	 *   "description" => [
	 *     "text" => "A Minecraft Server"
	 *   ],
	 *   "favicon" => "data:image/png;base64,&lt;data&gt;",
	 *   "ping" => 0.068003177642822
	 * ]</pre>
	 * Note that a server might not present all of these values, so always check with `isset` first.
	 * Also, the `description` is a chat object, so you can pass it to Phpcraft::chatToText().
	 *
	 * @param string $server_name
	 * @param integer $server_port
	 * @param float $timeout The amount of seconds to wait for a response with each method.
	 * @param integer $method The method(s) used to get the status. 2 = legacy list ping, 1 = modern list ping, 0 = both.
	 * @return array
	 * @throws IOException
	 */
	static function getServerStatus(string $server_name, int $server_port = 25565, float $timeout = 3.000, int $method = 0)
	{
		if($method != 2)
		{
			if($stream = @fsockopen($server_name, $server_port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream, Versions::protocol(false)[0]);
				$start = microtime(true);
				$con->sendHandshake($server_name, $server_port, 1);
				$con->writeVarInt(0x00);
				$con->send();
				if($con->readPacket($timeout) === 0x00)
				{
					$json = json_decode($con->readString(), true);
					$json["ping"] = microtime(true) - $start;
					$con->close();
					return $json;
				}
				$con->close();
			}
		}
		if($method != 1)
		{
			if($stream = @fsockopen($server_name, $server_port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream, 73);
				$start = microtime(true);
				$con->writeByte(0xFE);
				$con->writeByte(0x01);
				$con->writeByte(0xFA);
				$con->writeShort(11);
				$con->writeRaw(mb_convert_encoding("MC|PingHost", "utf-16be"));
				$host = mb_convert_encoding($server_name, "utf-16be");
				$con->writeShort(strlen($host) + 7);
				$con->writeByte($con->protocol_version);
				$con->writeShort(strlen($server_name));
				$con->writeRaw($host);
				$con->writeInt($server_port);
				$con->send(true);
				if($con->readRawPacket($timeout))
				{
					$arr = explode("\x00\x00", substr($con->read_buffer, 9));
					$con->close();
					return [
						"version" => [
							"name" => mb_convert_encoding($arr[1], mb_internal_encoding(), "utf-16be")
						],
						"players" => [
							"max" => intval(mb_convert_encoding($arr[4], mb_internal_encoding(), "utf-16be")),
							"online" => intval(mb_convert_encoding($arr[3], mb_internal_encoding(), "utf-16be"))
						],
						"description" => Phpcraft::textToChat(mb_convert_encoding($arr[2], mb_internal_encoding(), "utf-16be")),
						"ping" => (microtime(true) - $start)
					];
				}
				$con->close();
			}
		}
		return [];
	}

	/**
	 * Calculates the "distance" between two RGB arrays (each 3 integers).
	 *
	 * @param $rgb1 integer[]
	 * @param $rgb2 integer[]
	 * @return integer
	 */
	static function colorDiff(array $rgb1, array $rgb2)
	{
		return abs($rgb1[0] - $rgb2[0]) + abs($rgb1[1] - $rgb2[1]) + abs($rgb1[2] - $rgb2[2]);
	}

	/**
	 * Recursively deletes a folder.
	 *
	 * @param string $path
	 */
	static function recursivelyDelete(string $path)
	{
		if(substr($path, -1) == "/")
		{
			$path = substr($path, 0, -1);
		}
		if(!file_exists($path))
		{
			return;
		}
		if(is_dir($path))
		{
			foreach(scandir($path) as $file)
			{
				if(!in_array($file, [
					".",
					".."
				]))
				{
					Phpcraft::recursivelyDelete($path."/".$file);
				}
			}
			rmdir($path);
		}
		else
		{
			unlink($path);
		}
	}
}
