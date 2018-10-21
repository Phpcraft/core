<?php
namespace Phpcraft;

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
		403 => "1.13.2-pre2"
	];
	private function __construct(){}

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

	static function getProfilesFile()
	{
		return Utils::getMinecraftFolder()."/launcher_profiles.json";
	}

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

	static function saveProfiles($profiles)
	{
		file_put_contents(Utils::getProfilesFile(), json_encode($profiles, JSON_PRETTY_PRINT));
	}

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

	static function getExtensionsMissingToGoOnline()
	{
		$extensions_needed = [];
		if(!extension_loaded("gmp"))
		{
			array_push($extensions_needed, "GMP");
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

	static function generateUUIDv4()
	{
		return sprintf("%04x%04x%04x%04x%04x%04x%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), (mt_rand(0, 0x0fff) | 0x4000), (mt_rand(0, 0x3fff) | 0x8000), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	static function httpPOST($url, $data)
	{
		$options = [
			"http" => [
				"header" => "Content-type: application/json\r\n",
				"method" => "POST",
				"content" => json_encode($data)
			]
		];
		$context  = stream_context_create($options);
		$res = @file_get_contents($url, false, $context);
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

	static function resolve($server)
	{
		$arr = explode(":", $server);
		if(count($arr) > 1)
		{
			return Utils::resolveName($arr[0], false).":".$arr[1];
		}
		return Utils::resolveName($server, true);
	}

	static function resolveName($server, $withPort = true)
	{
		if(ip2long($server) !== false) // No need to resolve IPs.
		{
			return $server.($withPort ? ":25565" : "");
		}
		if($res = @dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
		{
			$i = array_rand($res);
			return resolveName($res[$i]["target"], false).($withPort ? ":".$res[$i]["port"] : "");
		}
		return $server.($withPort ? ":25565" : "");
	}

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

	static function isProtocolVersionSupported($protocol_version)
	{
		return isset(Utils::$protocol_versions[$protocol_version]);
	}

	static function getMinecraftVersionFromProtocolVersion($protocol_version)
	{
		return Utils::$protocol_versions[$protocol_version];
	}

	static function sha1($str)
	{
		$gmp = gmp_import(sha1($str, true));
		if(gmp_cmp($gmp, gmp_init("0x8000000000000000000000000000000000000000")) >= 0)
		{
			$gmp = gmp_mul(gmp_add(gmp_xor($gmp, gmp_init("0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")), gmp_init(1)), gmp_init(-1));
		}
		return gmp_strval($gmp, 16);
	}

	static function chatToANSIText($chat, $parent = false, $translations = null)
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
			// TODO: Display messages using § codes properly as well
			return $chat;
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
			"strikethrough" => "9"
		];
		$text = "\033[0";
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
					array_push($with, Utils::chatToANSIText($extra, $chat, $translations));
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
		if(!$child)
		{
			$text .= "\033[0;97;40m";
		}
		if(isset($chat["extra"]))
		{
			foreach($chat["extra"] as $extra)
			{
				$text .= Utils::chatToANSIText($extra, $chat, $translations);
			}
			if(!$child)
			{
				$text .= "\033[0;97;40m";
			}
		}
		return $text;
	}
}

class Client
{
	private $name;
	private $username;
	private $profileId = null;
	private $accessToken = null;

	function __construct($name)
	{
		$this->name = $name;
		$this->username = $name;
	}

	function getName()
	{
		return $this->name;
	}

	function getUsername()
	{
		return $this->username;
	}

	function isOnline()
	{
		return $this->profileId != null && $this->accessToken != null;
	}

	function getProfileId()
	{
		return $this->profileId;
	}

	function getAccessToken()
	{
		return $this->accessToken;
	}

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

class Exception extends \Exception
{
	function __construct($message)
	{
		parent::__construct($message);
	}
}

class Connection
{
	protected $stream;
	protected $protocol_version;
	protected $compression_threshold = false;
	protected $write_buffer = "";
	protected $read_buffer = "";

	function __construct($stream, $protocol_version)
	{
		$this->stream = $stream;
		$this->protocol_version = $protocol_version;
	}

	function getProtocolVersion()
	{
		return $this->protocol_version;
	}

	function isOpen()
	{
		return !feof($this->stream);
	}

	function close()
	{
		@fclose($this->stream);
	}

	function writeByte($byte)
	{
		$this->write_buffer .= pack("c", $byte);
	}
	function writeBoolean($value)
	{
		$this->write_buffer .= pack("c", ($value ? 1 : 0));
	}
	function writeVarInt($value)
	{
		$this->write_buffer .= Utils::intToVarInt($value);
	}
	function writeString($string)
	{
		$this->write_buffer .= Utils::intToVarInt(strlen($string)).$string;
	}
	function writeShort($short)
	{
		$this->write_buffer .= pack("n", $short);
	}
	function writeFloat($float)
	{
		$this->write_buffer .= pack("G", $float);
	}
	function writeLong($long)
	{
		$this->write_buffer .= pack("J", $long);
	}
	function writeDouble($double)
	{
		$this->write_buffer .= pack("E", $double);
	}
	function writePosition($x, $y, $z)
	{
		$this->writeLong((($x & 0x3FFFFFF) << 38) | (($y & 0xFFF) << 26) | ($z & 0x3FFFFFF));
	}
	function startPacket($name)
	{
		$this->write_buffer = Utils::intToVarInt(Packet::getId($name, $this->protocol_version));
	}
	function sendPacket()
	{
		$length = strlen($this->write_buffer);
		if($this->compression_threshold)
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

	function readPacket($forcefully = true)
	{
		$length = 0;
		$read = 0;
		do
		{
			$byte = fgetc($this->stream);
			if($byte === false)
			{
				if(!$forcefully && $read == 0)
				{
					return false;
				}
				while($byte === false)
				{
					$byte = fgetc($this->stream);
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
		if($this->compression_threshold !== false)
		{
			$uncompressed_length = $this->readVarInt();
			if($uncompressed_length > 0)
			{
				$this->read_buffer = gzuncompress($this->read_buffer, $uncompressed_length);
			}
		}
		return $this->readVarInt();
	}
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
	function readPosition()
	{
		$val = readLong(false);
		$x = $val >> 38;
		$y = ($val >> 26) & 0xFFF;
		$z = $val << 38 >> 38;
		return "$x $y $z";
	}
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
	function ignoreBytes($bytes)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new \Phpcraft\Exception("There are less than {$bytes} bytes");
		}
		$this->read_buffer = substr($this->read_buffer, $bytes);
	}
}

class ServerConnection extends Connection
{
	function __construct($server_name, $server_port, $protocol_version, $next_state)
	{
		if(!($stream = fsockopen($server_name, $server_port, $errno, $errstr, 10)))
		{
			throw new \Phpcraft\Exception($errstr);
		}
		stream_set_blocking($stream, false);
		parent::__construct($stream, $protocol_version);
		$this->writeVarInt(0x00);
		$this->writeVarInt($protocol_version);
		$this->writeString($server_name);
		$this->writeShort($server_port);
		$this->writeVarInt($next_state);
		$this->sendPacket();
	}
}

class ServerStatusConnection extends ServerConnection
{
	function __construct($server_name, $server_port = 25565)
	{
		parent::__construct($server_name, $server_port, -1, 1);
	}

	function getStatus()
	{
		$this->writeVarInt(0x00);
		$this->sendPacket();
		if($this->readPacket() != 0x00)
		{
			throw new \Phpcraft\Exception("Invalid response to status request: {$id} ".bin2hex($this->read_buffer)."\n");
		}
		return json_decode($this->readString(), true);
	}

	function measurePing()
	{
		$start = microtime(true);
		$this->writeVarInt(0x01);
		$this->writeLong(time());
		$this->sendPacket();
		$this->readPacket();
		return microtime(true) - $start;
	}
}

class ServerPlayConnection extends ServerConnection
{
	private $uuid;

	function __construct($protocol_version, $server_name, $server_port = 25565)
	{
		parent::__construct($server_name, $server_port, $protocol_version, 2);
	}

	function getUUID()
	{
		return $this->uuid;
	}

	function login($client)
	{
		$this->writeVarInt(0x00);
		$this->writeString($client->getUsername());
		$this->sendPacket();
		do
		{
			$id = $this->readPacket();
			if($id == 0x04) // Login Plugin Request
			{
				$this->writeVarInt(0x02); // Login Plugin Response
				$this->writeVarInt($this->readVarInt());
				$this->writeBoolean(false);
				$this->sendPacket();
			}
			else if($id == 0x03) // Set Compression
			{
				$this->compression_threshold = $this->readVarInt();
				if($this->compression_threshold < 1)
				{
					$this->compression_threshold = false;
				}
			}
			else if($id == 0x02) // Login Success
			{
				$this->uuid = $this->readString(36);
				$name = $this->readString(16);
				if($client->getUsername() != $name)
				{
					return "Server did not accept our username and would rather call us '{$name}'.";
				}
				return "";
			}
			else if($id == 0x01) // Encryption Request
			{
				if(!$client->isOnline())
				{
					return " This server is in online mode.";
				}
				$server_id = $this->readString(20);
				$publicKey = $this->readString();
				$verify_token = $this->readString();
				$shared_secret = "";
				for($i = 0; $i < 16; $i++)
				{
					$shared_secret .= chr(rand(0, 255));
				}
				if(Utils::httpPOST("https://sessionserver.mojang.com/session/minecraft/join", [
					"accessToken" => $client->getAccessToken(),
					"selectedProfile" => $client->getProfileId(),
					"serverId" => Utils::sha1($server_id.$shared_secret.$publicKey)
				]) === false)
				{
					return "The session server is down for maintenance.";
				}
				$publicKey = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".base64_encode($publicKey)."\n-----END PUBLIC KEY-----");
				$this->writeVarInt(0x01); // Encryption Response
				$crypted = "";
				openssl_public_encrypt($shared_secret, $crypted, $publicKey, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				openssl_public_encrypt($verify_token, $crypted, $publicKey, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				$this->sendPacket();
				$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
				stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
				stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
			}
			else if($id == 0x00) // Disconnect
			{
				return \Phpcraft\Utils::chatToANSIText(json_decode($this->readString(), true));
			}
			else
			{
				throw new \Phpcraft\Exception("Unexpected response: {$id} ".bin2hex($this->read_buffer)."\n");
			}
		}
		while(true);
	}
}

abstract class Packet
{
	private static $packet_ids = [
		// Clientbound
		"spawn_player" => [0x05, 0x05, 0x05, 0x05, 0x05, 0x0C],
		"chat_message" => [0x0E, 0x0F, 0x0F, 0x0F, 0x0F, 0x02],
		"disconnect" => [0x1B, 0x1A, 0x1A, 0x1A, 0x1A, 0x40],
		"open_window" => [0x14, 0x13, 0x13, 0x13, 0x13, 0x2D],
		"keep_alive_request" => [0x21, 0x1F, 0x1F, 0x1F, 0x1F, 0x00],
		"join_game" => [0x25, 0x23, 0x23, 0x23, 0x23, 0x01],
		"entity_relative_move" => [0x28, 0x26, 0x26, 0x25, 0x25, 0x15],
		"entity_look_and_relative_move" => [0x29, 0x27, 0x27, 0x26, 0x26, 0x17],
		"entity_look" => [0x2A, 0x28, 0x28, 0x27, 0x27, 0x16],
		"player_list_item" => [0x30, 0x2E, 0x2D, 0x2D, 0x2D, 0x38],
		"teleport" => [0x32, 0x2F, 0x2E, 0x2E, 0x2E, 0x08],
		"destroy_entites" => [0x35, 0x32, 0x31, 0x30, 0x30, 0x13],
		"respawn" => [0x38, 0x35, 0x34, 0x33, 0x33, 0x07],
		"update_health" => [0x44, 0x41, 0x40, 0x3E, 0x3E, 0x06],
		"entity_teleport" => [0x50, 0x4C, 0x4B, 0x49, 0x4A, 0x18],
		// Serverbound
		"teleport_confirm" => [0x00, 0x00, 0x00, 0x00, 0x00, -1],
		"send_chat_message" => [0x02, 0x02, 0x03, 0x02, 0x02, 0x01],
		"client_status" => [0x03, 0x03, 0x04, 0x03, 0x03, 0x16],
		"client_settings" => [0x04, 0x04, 0x05, 0x04, 0x04, 0x15],
		"close_window" => [0x09, 0x08, 0x09, 0x08, 0x08, 0x0D],
		"send_plugin_message" => [0x0A, 0x09, 0x0A, 0x09, 0x09, 0x17],
		"keep_alive_response" => [0x0E, 0x0B, 0x0C, 0x0B, 0x0B, 0x00],
		"player" => [0x0F, 0x0C, 0x0D, 0x0F, 0x0F, 0x03],
		"player_position" => [0x10, 0x0D, 0x0E, 0x0C, 0x0C, 0x04],
		"player_position_and_look" => [0x11, 0x0E, 0x0F, 0x0D, 0x0D, 0x06],
		"player_look" => [0x12, 0x0F, 0x10, 0x0E, 0x0E, 0x05],
		"held_item_change" => [0x21, 0x1A, 0x1A, 0x17, 0x17, 0x09],
		"animation" => [0x27, 0x1D, 0x1D, 0x1A, 0x1A, 0x0A],
		"player_block_placement" => [0x29, 0x1F, 0x1F, 0x1C, 0x1C, 0x08],
		"use_item" => [0x2A, 0x20, 0x20, 0x1D, 0x1D, -1],
	];
	protected $name;

	protected function __construct($name)
	{
		$this->name = $name;
	}

	function getName()
	{
		return $this->name;
	}

	static function getId($name, $protocol_version)
	{
		if($protocol_version >= 393)
		{
			return Packet::$packet_ids[$name][0];
		}
		else if($protocol_version >= 336)
		{
			return Packet::$packet_ids[$name][1];
		}
		else if($protocol_version >= 328)
		{
			return Packet::$packet_ids[$name][2];
		}
		else if($protocol_version >= 314)
		{
			return Packet::$packet_ids[$name][3];
		}
		else if($protocol_version >= 107)
		{
			return Packet::$packet_ids[$name][4];
		}
		return Packet::$packet_ids[$name][5];
	}

	function idFor($protocol_version)
	{
		return Packet::getId($this->name, $protocol_version);
	}

	static function idToName($id, $protocol_version)
	{
		foreach(Packet::$packet_ids as $n => $v)
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
			else if($protocol_version >= 107)
			{
				if($v[4] == $id)
				{
					return $n;
				}
			}
			else if($v[5] == $id)
			{
				return $n;
			}
		}
		return null;
	}

	abstract static function read($con);
	abstract function send($con);
}

abstract class KeepAlivePacket extends Packet
{
	protected $keepAliveId;

	function __construct($name, $keepAliveId)
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

	function getKeepAliveId()
	{
		return $this->keepAliveId;
	}

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
		$con->sendPacket();
	}
}
class KeepAliveRequestPacket extends KeepAlivePacket
{
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_request", $keepAliveId);
	}

	static function read($con)
	{
		if($con->getProtocolVersion() >= 339)
		{
			return new KeepAliveRequestPacket($con->readLong());
		}
		else
		{
			return new KeepAliveRequestPacket($con->readVarInt());
		}
	}

	function getResponse()
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}
}
class KeepAliveResponsePacket extends KeepAlivePacket
{
	function __construct($keepAliveId = null)
	{
		parent::__construct("keep_alive_response", $keepAliveId);
	}

	static function read($con)
	{
		if($con->getProtocolVersion() >= 339)
		{
			return new KeepAliveResponsePacket($con->readLong());
		}
		else
		{
			return new KeepAliveResponsePacket($con->readVarInt());
		}
	}
}
