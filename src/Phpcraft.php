<?php
namespace Phpcraft;
use hellsh\UUID;
use Phpcraft\Exception\IOException;
abstract class Phpcraft
{
	const SRC_DIR = __DIR__;
	const INSTALL_DIR = self::SRC_DIR.'/..';
	const BIN_DIR = self::INSTALL_DIR.'/bin';
	const DATA_DIR = self::INSTALL_DIR.'/data';
	/**
	 * @deprecated Use ChatComponent::FORMAT_NONE, instead.
	 */
	const FORMAT_NONE = 0;
	/**
	 * @deprecated Use ChatComponent::FORMAT_ANSI, instead.
	 */
	const FORMAT_ANSI = 1;
	/**
	 * @deprecated Use ChatComponent::FORMAT_SILCROW, instead.
	 */
	const FORMAT_SILCROW = 2;
	/**
	 * @deprecated Use ChatComponent::FORMAT_AMPERSAND, instead.
	 */
	const FORMAT_AMPERSAND = 3;
	/**
	 * @deprecated Use ChatComponent::FORMAT_HTML, instead.
	 */
	const FORMAT_HTML = 4;
	/**
	 * Modern list ping. Legacy if that fails.
	 */
	const METHOD_ALL = 0;
	const METHOD_MODERN = 1;
	const METHOD_LEGACY = 2;
	/**
	 * @var Configuration $json_cache
	 */
	public static $json_cache;
	/**
	 * @var Configuration $user_cache
	 */
	public static $user_cache;
	private static $profiles;

	/**
	 * Returns the contents of Minecraft's launcher_profiles.json with some values being set if they are unset.
	 *
	 * @param bool $bypass_cache Set this to true if you anticipate external changes to the file.
	 * @return array
	 * @see Phpcraft::getProfilesFile()
	 * @see Phpcraft::saveProfiles()
	 */
	static function getProfiles(bool $bypass_cache = false): array
	{
		if($bypass_cache || self::$profiles === null)
		{
			$profiles_file = self::getProfilesFile();
			if(file_exists($profiles_file) && is_file($profiles_file))
			{
				self::$profiles = json_decode(file_get_contents($profiles_file), true);
			}
			else
			{
				self::$profiles = [];
			}
			if(empty(self::$profiles["clientToken"]))
			{
				self::$profiles["clientToken"] = UUID::v4()
													 ->__toString();
			}
			if(!isset(self::$profiles["authenticationDatabase"]))
			{
				self::$profiles["authenticationDatabase"] = [];
			}
		}
		return self::$profiles;
	}

	/**
	 * Returns the path of Minecraft's launcher_profiles.json.
	 *
	 * @return string
	 */
	static function getProfilesFile(): string
	{
		return self::getMinecraftFolder()."/launcher_profiles.json";
	}

	/**
	 * Returns the path of the .minecraft folder without a folder seperator at the end.
	 *
	 * @return string
	 */
	static function getMinecraftFolder(): string
	{
		if(self::isWindows())
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
	static function isWindows(): bool
	{
		return defined("PHP_WINDOWS_VERSION_MAJOR");
	}

	/**
	 * Saves the profiles array into Minecraft's launcher_profiles.json.
	 *
	 * @param array $profiles
	 * @return void
	 */
	static function saveProfiles(array $profiles): void
	{
		self::$profiles = $profiles;
		file_put_contents(self::getProfilesFile(), json_encode(self::$profiles, JSON_PRETTY_PRINT));
	}

	/**
	 * Returns the contents of a JSON file as associative array with additional memory and disk caching levels.
	 *
	 * @param string $url The URL of the resource.
	 * @return array
	 * @see Phpcraft::maintainCache
	 */
	static function getCachableJson(string $url): array
	{
		if(!self::$json_cache->data && is_file(self::$json_cache->file))
		{
			if(filemtime(self::$json_cache->file) < time() - 86400)
			{
				self::maintainCache();
			}
			if(is_file(self::$json_cache->file))
			{
				self::$json_cache->data = json_decode(file_get_contents(self::$json_cache->file), true);
			}
		}
		if(!self::$json_cache->has($url) || self::$json_cache->data[$url]["expiry"] < time())
		{
			self::$json_cache->set($url, [
				"contents" => json_decode(file_get_contents($url), true),
				"expiry" => time() + 86400
			]);
		}
		return self::$json_cache->data[$url]["contents"];
	}

	/**
	 * Deletes expired cache entries.
	 *
	 * @return void
	 * @see getCachableJson
	 * @see getCachableResource
	 */
	static function maintainCache(): void
	{
		if(!is_file(self::$json_cache->file))
		{
			return;
		}
		$time = time();
		foreach(self::$json_cache->data as $url => $entry)
		{
			if($entry["expiry"] < $time)
			{
				self::$json_cache->unset($url);
			}
		}
	}

	/**
	 * Sends an HTTP POST request with a JSON payload.
	 * The response will always contain a "status" value which will be the HTTP response code, e.g. 200.
	 *
	 * @param string $url
	 * @param array $data
	 * @return array
	 */
	static function httpPOST(string $url, array $data): array
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
	static function resolve(string $server): string
	{
		$arr = explode(":", $server);
		if(count($arr) > 1)
		{
			return self::resolveName($arr[0], false).":".$arr[1];
		}
		return self::resolveName($server, true);
	}

	private static function resolveName(string $server, bool $withPort = true): string
	{
		if(ip2long($server) === false && $res = @dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
		{
			$i = array_rand($res);
			return self::resolveName($res[$i]["target"], false).($withPort ? ":".$res[$i]["port"] : "");
		}
		return $server.($withPort ? ":25565" : "");
	}

	static function binaryStringToHex(string $str): string
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
	static function sha1(string $str): string
	{
		$gmp = gmp_import(sha1($str, true));
		if(gmp_cmp($gmp, gmp_init("0x8000000000000000000000000000000000000000")) >= 0)
		{
			$gmp = gmp_mul(gmp_add(gmp_xor($gmp, gmp_init("0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")), gmp_init(1)), gmp_init(-1));
		}
		return gmp_strval($gmp, 16);
	}

	/**
	 * @param array|string|null|ChatComponent $chat
	 * @param int $format
	 * @param array<string,string>|null $translations
	 * @return string
	 * @deprecated Use ChatComponent::cast($chat)->toString($format), instead.
	 */
	static function chatToText($chat, int $format = ChatComponent::FORMAT_NONE, ?array $translations = null): string
	{
		if($translations !== null && count($translations) > count(ChatComponent::$translations))
		{
			ChatComponent::$translations = $translations;
		}
		return ChatComponent::cast($chat)
							->toString($format);
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
	 * Note that a server might not present all of these values, so always check with `isset` or `array_key_exists` first.
	 * `description` should always be a valid chat component.
	 *
	 * @param string $server_name
	 * @param int $server_port
	 * @param float $timeout The amount of seconds to wait for a response with each method.
	 * @param int $method The method(s) used to get the status. 2 = legacy list ping, 1 = modern list ping, 0 = both.
	 * @return array
	 * @throws IOException
	 */
	static function getServerStatus(string $server_name, int $server_port = 25565, float $timeout = 3.000, int $method = Phpcraft::METHOD_ALL): array
	{
		if($method != Phpcraft::METHOD_LEGACY)
		{
			if($stream = @fsockopen($server_name, $server_port, $errno, $errstr, $timeout))
			{
				$con = new ServerConnection($stream, Versions::protocol(false)[0]);
				$start = microtime(true);
				$con->sendHandshake($server_name, $server_port, Connection::STATE_STATUS);
				$con->writeVarInt(0x00); // Status Request
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
		if($method != Phpcraft::METHOD_MODERN)
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
						"description" => ChatComponent::text(mb_convert_encoding($arr[2], mb_internal_encoding(), "utf-16be"))
													  ->toArray(),
						"ping" => (microtime(true) - $start)
					];
				}
				$con->close();
			}
		}
		return [];
	}

	/**
	 * @param string $text
	 * @param boolean $allow_amp
	 * @return array
	 * @deprecated Use ChatComponent::text($text, $allow_amp)->toArray(), instead.
	 */
	static function textToChat(string $text, bool $allow_amp = false): array
	{
		return ChatComponent::text($text, $allow_amp)
							->toArray();
	}

	/**
	 * Calculates the "distance" between two RGB arrays (each 3 integers).
	 *
	 * @param array{int,int,int} $rgb1
	 * @param array{int,int,int} $rgb2
	 * @return int
	 */
	static function colorDiff(array $rgb1, array $rgb2): int
	{
		return abs($rgb1[0] - $rgb2[0]) + abs($rgb1[1] - $rgb2[1]) + abs($rgb1[2] - $rgb2[2]);
	}

	/**
	 * Recursively deletes a folder.
	 *
	 * @param string $path
	 * @return void
	 */
	static function recursivelyDelete(string $path): void
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
					self::recursivelyDelete($path."/".$file);
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

Phpcraft::$json_cache = new Configuration(__DIR__."/.json_cache");
Phpcraft::$user_cache = new Configuration(__DIR__."/.user_cache");
