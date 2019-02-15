<?php
namespace Phpcraft;
class Phpcraft
{
	private static $minecraft_folder = null;

	private static function versions()
	{
		return [
			"1.13.2" => 404,
			"1.13.2-pre2" => 403,
			"1.13.2-pre1" => 402,
			"1.13.1" => 401,
			"1.13" => 393,
			"1.12.2" => 340,
			"1.12.2-pre2" => 339,
			"1.12.1" => 338,
			"1.12.1-pre2" => 337,
			"1.12.1-pre1" => 337,
			"17w31a" => 336,
			"1.12" => 335,
			"1.11.2" => 316,
			"1.11.1" => 316,
			"1.11" => 315,
			"1.10.2" => 210,
			"1.10.1" => 210,
			"1.10" => 210,
			"1.9.4" => 110,
			"1.9.3" => 110,
			"1.9.2" => 109,
			"1.9.1" => 108,
			"1.9" => 107,
			"1.8.9" => 47,
			"1.8.8" => 47,
			"1.8.7" => 47,
			"1.8.6" => 47,
			"1.8.5" => 47,
			"1.8.4" => 47,
			"1.8.3" => 47,
			"1.8.2" => 47,
			"1.8.1" => 47,
			"1.8" => 47
		];
	}

	/**
	 * Returns the path of the .minecraft folder without a folder seperator at the end.
	 * @return string
	 */
	static function getMinecraftFolder()
	{
		if(Phpcraft::$minecraft_folder === null)
		{
			if(stristr(PHP_OS, "LINUX"))
			{
				Phpcraft::$minecraft_folder = getenv("HOME")."/.minecraft";
			}
			else if(stristr(PHP_OS, "DAR"))
			{
				Phpcraft::$minecraft_folder = getenv("HOME")."/Library/Application Support/minecraft";
			}
			else if(stristr(PHP_OS, "WIN"))
			{
				Phpcraft::$minecraft_folder = getenv("APPDATA")."\\.minecraft";
			}
			else
			{
				Phpcraft::$minecraft_folder = __DIR__."/.minecraft";
			}
			if(!file_exists(Phpcraft::$minecraft_folder) || !is_dir(Phpcraft::$minecraft_folder))
			{
				mkdir(Phpcraft::$minecraft_folder);
			}
		}
		return Phpcraft::$minecraft_folder;
	}

	/**
	 * Returns the path of Minecraft's launcher_profiles.json.
	 * @return string
	 */
	static function getProfilesFile()
	{
		return Phpcraft::getMinecraftFolder()."/launcher_profiles.json";
	}

	/**
	 * Returns the contents of Minecraft's launcher_profiles.json with some values being set if they are unset.
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
			$profiles["clientToken"] = Phpcraft::generateUUIDv4();
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
	 * Saves the profiles array into Minecraft's launcher_profiles.json.
	 * @param array $profiles
	 * @return void
	 */
	static function saveProfiles($profiles)
	{
		file_put_contents(Phpcraft::getProfilesFile(), json_encode($profiles, JSON_PRETTY_PRINT));
	}

	/**
	 * Returns the JSON-decoded content of the assets index of the latest version.
	 * @return array
	 */
	static function getAssetIndex()
	{
		$assets_dir = Phpcraft::getMinecraftFolder()."/assets";
		if(!file_exists($assets_dir) || !is_dir($assets_dir))
		{
			mkdir($assets_dir);
		}
		$assets_index_dir = $assets_dir."/indexes";
		if(!file_exists($assets_index_dir) || !is_dir($assets_index_dir))
		{
			mkdir($assets_index_dir);
		}
		$index_file = $assets_index_dir."/1.13.1.json";
		if(!file_exists($index_file))
		{
			$index = file_get_contents("https://launchermeta.mojang.com/v1/packages/f776dabd6239938411e2f123837f4005b74e49f8/1.13.1.json");
			file_put_contents($index_file, $index);
		}
		else
		{
			$index = file_get_contents($index_file);
		}
		return json_decode($index, true);
	}

	/**
	 * Checks the asset index for the existence of an asset.
	 * @return boolean
	 */
	static function doesAssetExist($name)
	{
		return isset(Phpcraft::getAssetIndex()["objects"][$name]);
	}

	/**
	 * Downloads an asset by name and returns the path to the downloaded file on success or null on failure.
	 * @param string $name
	 * @return string
	 */
	static function downloadAsset($name)
	{
		$index = Phpcraft::getAssetIndex();
		$objects_dir = Phpcraft::getMinecraftFolder()."/assets/objects";
		if(!file_exists($objects_dir) || !is_dir($objects_dir))
		{
			mkdir($objects_dir);
		}
		if($asset = $index["objects"][$name])
		{
			$hash = $index["objects"][$name]["hash"];
			$dir = $objects_dir."/".substr($hash, 0, 2);
			if(!file_exists($dir) || !is_dir($dir))
			{
				mkdir($dir);
			}
			$file = $dir."/".$hash;
			file_put_contents($file, file_get_contents("http://resources.download.minecraft.net/".substr($hash, 0, 2)."/".$hash));
			return $file;
		}
		return null;
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
	static function addHypensToUUID($uuid)
	{
		if(strlen($uuid) != 32)
		{
			return $uuid;
		}
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
	 * @param string $server The server address, e.g. localhost
	 * @return string The resolved address, e.g. localhost:25565
	 */
	static function resolve($server)
	{
		$arr = explode(":", $server);
		if(count($arr) > 1)
		{
			return Phpcraft::resolveName($arr[0], false).":".$arr[1];
		}
		return Phpcraft::resolveName($server, true);
	}

	private static function resolveName($server, $withPort = true)
	{
		if(ip2long($server) === false && $res = @dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
		{
			$i = array_rand($res);
			return Phpcraft::resolveName($res[$i]["target"], false).($withPort ? ":".$res[$i]["port"] : "");
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
		if($value < 0)
		{
			$value = ((($value ^ 0xFFFFFFFF) + 1) * -1);
		}
		$bytes = "";
		do
		{
			$temp = ($value & 0b01111111);
			$value = ($value >> 7);
			if($value != 0)
			{
				$temp |= 0b10000000;
			}
			$bytes .= pack("C", $temp);
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
		return in_array($protocol_version, Phpcraft::versions());
	}

	/**
	 * Returns an array of Minecraft versions corresponding to the given protocol version, newest first.
	 * @param integer $protocol_version e.g., 340 for ["1.12.2"]
	 * @return array
	 */
	static function getMinecraftVersionsFromProtocolVersion($protocol_version)
	{
		$minecraft_versions = [];
		foreach(Phpcraft::versions() as $k => $v)
		{
			if($v == $protocol_version)
			{
				array_push($minecraft_versions, $k);
			}
		}
		return $minecraft_versions;
	}

	/**
	 * Returns a human-readable range of Minecraft versions corresponding to the given protocol version.
	 * @param integer $protocol_version e.g., 47 for 1.8 - 1.8.9
	 * @return string The version range or an empty string if the given protocol version is not supported.
	 */
	static function getMinecraftVersionRangeFromProtocolVersion($protocol_version)
	{
		$minecraft_versions = \Phpcraft\Phpcraft::getMinecraftVersionsFromProtocolVersion($protocol_version);
		$count = count($minecraft_versions);
		if($count == 0)
		{
			return "";
		}
		if($count == 1)
		{
			return $minecraft_versions[0];
		}
		return $minecraft_versions[$count - 1]." - ".$minecraft_versions[0];
	}

	/**
	 * Returns whether a given Minecraft version is supported.
	 * @param string $minecraft_version e.g., 1.12.2
	 * @return boolean
	 */
	static function isMinecraftVersionSupported($minecraft_version)
	{
		return isset(Phpcraft::versions()[$minecraft_version]);
	}

	/**
	 * Returns the Minecraft version corresponding to the given protocol version.
	 * @param string $minecraft_version e.g., 1.12.2 for 340
	 * @return integer The protocol version or null if the Minecraft version is not supported.
	 */
	static function getProtocolVersionFromMinecraftVersion($minecraft_version)
	{
		return @Phpcraft::versions()[$minecraft_version];
	}

	/**
	 * Returns a list of supported Protocol versions.
	 * @return string[]
	 */
	static function getSupportedProtocolVersions()
	{
		return array_values(Phpcraft::versions());
	}

	/**
	 * Returns a list of supported Minecraft versions.
	 * @return string[]
	 */
	static function getSupportedMinecraftVersions()
	{
		$minecraft_versions = [];
		foreach(Phpcraft::versions() as $k => $v)
		{
			array_push($minecraft_versions, $k);
		}
		return $minecraft_versions;
	}

	/**
	 * Generates a Minecraft-style SHA1 hash.
	 * This function requires GMP to be installed, but is only needed when going online.
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
	 * @param boolean $allowAmp If true, '&' will be handled like '§'.
	 * @param integer $i Ignore this parameter.
	 * @param boolean $child Ignore this parameter.
	 */
	static function textToChat($str, $allowAmp = false, &$i = 0, $child = false)
	{
		if(strpos($str, "§") === false && (!$allowAmp || strpos($str, "&") === false))
		{
			return ["text" => $str];
		}
		if(!$child && $i == 0 && (strpos(mb_substr($str, 2, null, "utf-8"), "§r") !== false || ($allowAmp && strpos(mb_substr($str, 2, null, "utf-8"), "&r") !== false)))
		{
			$extras = [];
			while($i < mb_strlen($str, "utf-8"))
			{
				array_push($extras, Phpcraft::textToChat($str, $allowAmp, $i, true));
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
			if($c == "§" || ($allowAmp && $c == "&"))
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
					$component = Phpcraft::textToChat($str, $allowAmp, $i, true);
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
	 * Converts a chat object into text.
	 * @param array|string $chat The chat object as an array or string.
	 * @param integer $format 0 = Dispose of color and formatting. 1 = Convert to ANSI escape codes (for colors in the console). 2 = Convert to paragraph (§) formatting.
	 * @param array $translations The translations array so translated messages look proper.
	 * @param array $parent Ignore this parameter.
	 * @return string
	 */
	static function chatToText($chat, $format = 0, $translations = null, $parent = [])
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
			$chat = Phpcraft::textToChat($chat);
		}
		if($format > 0)
		{
			if($format == 2)
			{
				$text = "§r";
			}
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
			else
			{
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
					if(isset($parent[$n]))
					{
						$chat[$n] = $parent[$n];
					}
				}
				if(isset($chat[$n]) && $chat[$n])
				{
					if($format == 1)
					{
						array_push($ansi_modifiers, $v);
					}
					else
					{
						$text .= "§".$v;
					}
				}
			}
			if($format == 1)
			{
				$text = "\x1B[".join(";", $ansi_modifiers)."m";
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
				if($format == 1)
				{
					if(isset($colors[$chat["color"]]))
					{
						array_push($ansi_modifiers, $colors[$chat["color"]]);
					}
				}
				else if(($i = array_search($chat["color"], array_keys($colors))) !== false)
				{
					$text .= "§".dechex($i);
				}
			}
		}
		else
		{
			$text = "";
		}
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
			if(strpos($chat["text"], "§") !== false)
			{
				$chat = Phpcraft::textToChat($chat["text"]) + $chat;
			}
			$text .= $chat["text"];
		}
		if(isset($chat["extra"]))
		{
			foreach($chat["extra"] as $extra)
			{
				$text .= Phpcraft::chatToText($extra, $format, $translations, $chat);
			}
		}
		return $text;
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
	 *
	 * Note that a server might not present all of these values, so always check with `isset` first.
	 *
	 * Also, the `description` is a chat object, so you can pass it to Phpcraft::chatToANSIText().
	 * @param string $server_name
	 * @param integer $server_port
	 * @param float $timeout The amount of seconds to wait for a response with each method.
	 * @param integer $method The method(s) used to get the status. 2 = legacy list ping, 1 = modern list ping, 0 = both.
	 * @return array
	 */
	static function getServerStatus($server_name, $server_port = 25565, $timeout = 3.000, $method = 0)
	{
		if($method != 2)
		{
			if($stream = @fsockopen($server_name, $server_port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream);
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
				$con = new ServerConnection($stream, 0);
				$start = microtime(true);
				$con->writeByte(0xFE);
				$con->writeByte(0x01);
				$con->writeByte(0xFA);
				$con->writeShort(11);
				$con->writeRaw(mb_convert_encoding("MC|PingHost", "utf-16be"));
				$host = mb_convert_encoding($server_name, "utf-16be");
				$con->writeShort(strlen($host) + 7);
				$con->writeByte(73); // Protocol Version
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
}
