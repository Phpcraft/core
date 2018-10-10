<?php
echo "\033[0;97;40mPHP Minecraft Client 1.1\n\n";

if(stristr(PHP_OS, "LINUX"))
{
	$os = "linux";
	$mcdir = getenv("HOME")."/.minecraft";
}
else if(stristr(PHP_OS, "DAR"))
{
	$os = "mac";
	$mcdir = getenv("HOME")."/Library/Application Support/minecraft";
}
else if(stristr(PHP_OS, "WIN"))
{
	$os = "windows";
	$mcdir = getenv("APPDATA")."\\.minecraft";
}
else
{
	$os = "unknown";
	$mcdir = "./.minecraft/";
}
if($os == "windows")
{
	$acknowledgements = [
		"Since you're on Windows, you shouldn't unfocus this window.", // https://bugs.php.net/bug.php?id=34972
		"If you're using Windows 8.1 or below, you won't see any colors."
	];
}
else
{
	$acknowledgements = [];
}
if(!file_exists($mcdir) || !is_dir($mcdir))
{
	mkdir($mcdir);
}

$options = [];
for($i = 1; $i < count($argv); $i++)
{
	$arg = $argv[$i];
	while(substr($arg, 0, 1) == "-")
	{
		$arg = substr($arg, 1);
	}
	if(($o = strpos($arg, "=")) !== false)
	{
		$n = substr($arg, 0, $o);
		$v = substr($arg, $o + 1);
	}
	else
	{
		$n = $arg;
		$v = "";
	}
	switch($n)
	{
		case "online":
		case "acknowledge":
		$options[$n] = true;
		break;

		case "name":
		case "server":
		case "langfile":
		case "joinmsg":
		case "locale":
		$options[$n] = $v;
		break;

		case "?":
		case "help":
		echo "online          use online mode\n";
		echo "name=<name>     skip name input and use <name> as name\n";
		echo "server=<server> skip server input and connect to <server>\n";
		echo "langfile=<file> load Minecraft translations from <file>\n";
		echo "acknowledge     automatically acknowledge all warnings\n";
		echo "joinmsg=<msg>   will send <msg> as soon as we are connected\n";
		echo "locale=<locale> sent to the server, default: en_US\n";
		exit;

		default:
		die("Unknown argument '{$n}' -- try 'help' for a list of arguments.\n");
	}
}

$translations = [
	"chat.type.text" => "<%s> %s",
	"chat.type.announcement" => "[%s] %s",
	"multiplayer.player.joined" => "%s joined the game",
	"multiplayer.player.left" => "%s left the game"
];
if(isset($options["langfile"]))
{
	if(!file_exists($options["langfile"]) || !is_file($options["langfile"]))
	{
		die($options["langfile"]." doesn't exist.\n");
	}
	$translations = json_decode(file_get_contents($options["langfile"]), true);
}
else
{
	$en_gb = "{$mcdir}/assets/objects/35/35ddd16739b85b353e5462c08c5ff018287383a2";
	if(file_exists($en_gb) && is_file($en_gb))
	{
		echo "Using en_gb.json from your Minecraft installation to translate messages.\n";
		$translations = json_decode(file_get_contents($en_gb), true);
	}
	else
	{
		array_push($acknowledgements, "No language file has been provided. Expect broken messages.");
	}
}

$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

$online = false;
if(isset($options["online"]))
{
	$online = true;
}
else
{
	echo "Would you like to join premium servers? (y/N) ";
	if(substr(trim(fgets($stdin)), 0, 1) == "y")
	{
		$online = true;
	}
}

$name = "";
if(isset($options["name"]))
{
	$name = $options["name"];
}
while(!$name)
{
	if($online)
	{
		echo "What's your Mojang account email address? (username if unmigrated) ";
		$name = trim(fgets($stdin));
	}
	else
	{
		echo "How would you like to be called in-game? [PHPMinecraftUser] ";
		$name = trim(fgets($stdin));
		if(!$name)
		{
			$name = "PHPMinecraftUser";
			break;
		}
	}
}
function httpPOST($url, $data)
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
	if($res)
	{
		return json_decode($res, true);
	}
	return $res;
}
if($online)
{
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
		die("To join online servers, you need mcrypt. Try: apt-get install php-mcrypt\n");
	}
	$profiles = [];
	$profiles_file = "{$mcdir}/launcher_profiles.json";
	if(file_exists($profiles_file) && is_file($profiles_file))
	{
		$profiles = json_decode(file_get_contents($profiles_file), true);
	}
	if(empty($profiles["clientToken"]))
	{
		$profiles["clientToken"] = sprintf("%04x%04x%04x%04x%04x%04x%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), (mt_rand(0, 0x0fff) | 0x4000), (mt_rand(0, 0x3fff) | 0x8000), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}
	if(!isset($profiles["selectedUser"]))
	{
		$profiles["selectedUser"] = [];
	}
	$foundAccount = false;
	if(isset($profiles["authenticationDatabase"]))
	{
		foreach($profiles["authenticationDatabase"] as $n => $v)
		{
			if($v["username"] == $name)
			{
				foreach($v["profiles"] as $u => $d)
				{
					$profiles["selectedUser"]["profile"] = $u;
					break;
				}
				$profiles["selectedUser"]["account"] = $n;
				$foundAccount = true;
				break;
			}
			else
			{
				foreach($v["profiles"] as $u => $d)
				{
					if($d["displayName"] == $name)
					{
						$profiles["selectedUser"]["profile"] = $u;
						$foundAccount = true;
						break;
					}
				}
				if($foundAccount)
				{
					$profiles["selectedUser"]["account"] = $n;
					break;
				}
			}
		}
	}
	else
	{
		$profiles["authenticationDatabase"] = [];
	}
	if($foundAccount)
	{
		if(httpPOST("https://authserver.mojang.com/validate", [
			"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
			"clientToken" => $profiles["clientToken"]
		]) === false)
		{
			if($res = httpPOST("https://authserver.mojang.com/refresh", [
				"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
				"clientToken" => $profiles["clientToken"]
			]))
			{
				if(isset($res["accessToken"]))
				{
					$profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"] = $res["accessToken"];
				}
				else
				{
					$foundAccount = false;
				}
			}
			else
			{
				$foundAccount = false;
			}
		}
	}
	if(!$foundAccount)
	{
		do
		{
			echo "What's your account password? (visible!) ";
			$pass = trim(fgets($stdin));
		}
		while(!$pass);
		if($res = httpPOST("https://authserver.mojang.com/authenticate", [
			"agent" => [
				"name" => "Minecraft",
				"version" => 1
			],
			"username" => $name,
			"password" => $pass,
			"clientToken" => $profiles["clientToken"]
		]))
		{
			var_dump($res);
			if(!isset($res["selectedProfile"]))
			{
				die("Your Mojang account does not have a Minecraft license.\n");
			}
			$profiles["authenticationDatabase"][$res["selectedProfile"]["userId"]] = [
				"accessToken" => $res["accessToken"],
				"username" => $name,
				"profiles" => [
					$res["selectedProfile"]["id"] => [
						"displayName" => $res["selectedProfile"]["name"]
					]
				]
			];
			$profiles["selectedUser"] = [
				"account" => $res["selectedProfile"]["userId"],
				"profile" => $res["selectedProfile"]["id"]
			];
		}
	}
	$name = $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["profiles"][$profiles["selectedUser"]["profile"]]["displayName"];
	file_put_contents($profiles_file, json_encode($profiles, JSON_PRETTY_PRINT));
}

function resolveName($server, $withPort = true)
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
$server = "";
if(isset($options["server"]))
{
	$server = $options["server"];
}
if(!$server)
{
	echo "What server would you like to join? [localhost] ";
	$server = trim(fgets($stdin));
	if(!$server)
	{
		$server = "localhost";
	}
}
echo "Resolving...";
$serverarr = explode(":", $server);
$port = "";
if(count($serverarr) > 1)
{
	$server = $serverarr[0];
	$port = $serverarr[1];
}
$server = resolveName($server, !$port).($port ? ":{$port}" : "");
echo " Resolved to {$server}\n";
$serverarr = explode(":", $server);
if(count($serverarr) != 2)
{
	die("Invalid DNS result\n");
}
$stream = false;
$compression_threshold = false;
function connect($protocolVersion, $nextState)
{
	global $stream, $serverarr, $compression_threshold;
	if($stream)
	{
		@fclose($stream);
	}
	$stream = fsockopen($serverarr[0], $serverarr[1], $errno, $errstr, 10) or die($errstr."\n");
	$compression_threshold = false;
	stream_set_blocking($stream, false);
	writeVarInt(0x00);
	writeVarInt($protocolVersion);
	writeString($serverarr[0]);
	writeShort($serverarr[1]);
	writeVarInt($nextState);
	sendPacket();
}
$write_buffer = "";
function intToVarInt($value)
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
function writeByte($byte)
{
	global $write_buffer;
	$write_buffer .= pack("c", $byte);
}
function writeBoolean($value)
{
	writeByte($value ? 1 : 0);
}
function writeVarInt($value)
{
	global $write_buffer;
	$write_buffer .= intToVarInt($value);
}
function writeString($string)
{
	global $write_buffer;
	$write_buffer .= intToVarInt(strlen($string)).$string;
}
function writeShort($short)
{
	global $write_buffer;
	$write_buffer .= pack("n", $short);
}
function writeLong($long)
{
	global $write_buffer;
	$write_buffer .= pack("J", $long);
}
function startPacket($name)
{
	global $write_buffer, $packet_ids;
	$write_buffer = "";
	writeVarInt($packet_ids[$name]);
}
function sendPacket()
{
	global $write_buffer, $stream, $compression_threshold;
	$length = strlen($write_buffer);
	if($compression_threshold)
	{
		if($length >= $compression_threshold)
		{
			$compressed = gzcompress($write_buffer, 1);
			$compressed_length_varint = intToVarInt(strlen($compressed));
			$length += strlen($compressed_length_varint);
			fwrite($stream, intToVarInt($length).$compressed_length_varint.$compressed);
		}
		else
		{
			fwrite($stream, intToVarInt($length + 1)."\x00".$write_buffer);
		}
	}
	else
	{
		fwrite($stream, intToVarInt($length).$write_buffer);
	}
	$write_buffer = "";
}
$read_buffer = "";
function readPacket($force = true)
{
	global $read_buffer, $stream, $compression_threshold;
	$length = 0;
	$read = 0;
	do
	{
		$byte = fgetc($stream);
		if($byte === false)
		{
			if(!$force && $read == 0)
			{
				return false;
			}
			while($byte === false)
			{
				$byte = fgetc($stream);
			}
		}
		$byte = ord($byte);
		$length |= (($byte & 0x7F) << ($read++ * 7));
		if($read > 5)
		{
			throw new Exception("VarInt is too big");
		}
		if(($byte & 0x80) != 128)
		{
			break;
		}
	}
	while(true);
	$read_buffer = fread($stream, $length);
	while(strlen($read_buffer) < $length)
	{
		$read_buffer .= fread($stream, $length - strlen($read_buffer));
	}
	if($compression_threshold)
	{
		$uncompressed_length = readVarInt();
		if($uncompressed_length > 0)
		{
			$read_buffer = gzuncompress($read_buffer, $uncompressed_length);
		}
	}
	$id = readVarInt();
	return $id;
}
function readVarInt()
{
	global $read_buffer;
	$value = 0;
	$read = 0;
	do
	{
		if(strlen($read_buffer) == 0)
		{
			throw new Exception("Not enough bytes to read VarInt\n");
		}
		$byte = ord(substr($read_buffer, 0, 1));
		$read_buffer = substr($read_buffer, 1);
		$value |= (($byte & 0x7F) << ($read++ * 7));
		if($read > 5)
		{
			throw new Exception("VarInt is too big\n");
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
	global $read_buffer;
	$length = readVarInt();
	if($length == 0)
	{
		return "";
	}
	if($length > (($maxLength * 4) + 3))
	{
		throw new Exception("String length {$length} exceeds maximum of ".(($maxLength * 4) + 3));
	}
	if($length > strlen($read_buffer))
	{
		throw new Exception("Not enough bytes to read string with length {$length}");
	}
	$str = substr($read_buffer, 0, $length);
	$read_buffer = substr($read_buffer, $length);
	return $str;
}
function readByte()
{
	global $read_buffer;
	if(strlen($read_buffer) < 1)
	{
		throw new Exception("Not enough bytes to read byte");
	}
	$short = unpack("cbyte", substr($read_buffer, 0, 1))["byte"];
	$read_buffer = substr($read_buffer, 1);
	return $short;
}
function readBoolean()
{
	return readByte() == 1;
}
function readShort()
{
	global $read_buffer;
	if(strlen($read_buffer) < 2)
	{
		throw new Exception("Not enough bytes to read short");
	}
	$short = unpack("nshort", substr($read_buffer, 0, 2))["short"];
	$read_buffer = substr($read_buffer, 2);
	return $short;
}
function readLong()
{
	global $read_buffer;
	if(strlen($read_buffer) < 8)
	{
		throw new Exception("Not enough bytes to read long");
	}
	$long = unpack("Jlong", substr($read_buffer, 0, 8))["long"];
	$read_buffer = substr($read_buffer, 8);
	return $long;
}
function readUUIDBytes()
{
	global $read_buffer;
	if(strlen($read_buffer) < 16)
	{
		throw new Exception("Not enough bytes to read UUID");
	}
	$uuid = substr($read_buffer, 0, 16);
	$read_buffer = substr($read_buffer, 16);
	return $uuid;
}
function readChat()
{
	return json_decode(readString(), true);
}
function chatToANSIText($chat, $parent = false)
{
	global $translations;
	if(gettype($chat) == "string")
	{
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
	$esc = [];
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
				array_push($with, chatToANSIText($extra, $chat));
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
			$text .= chatToANSIText($extra, $chat);
		}
		if(!$child)
		{
			$text .= "\033[0;97;40m";
		}
	}
	return $text;
}

$supportedVersions = [
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
	401 => "1.13.1"
];
echo "Determining version";
connect(-1, 0x01);
echo ".";
writeVarInt(0x00);
sendPacket();
echo ".";
$id = readPacket();
echo ".";
if($id != 0x00)
{
	die(" Invalid response: {$id} {$read_buffer}\n");
}
$info = json_decode(readString(), true);
if(!isset($info["version"]) || !isset($info["version"]["protocol"]))
{
	die(" Invalid response:\n".json_encode($info)."\n");
}
$protocolVersion = $info["version"]["protocol"];
if(isset($supportedVersions[$protocolVersion]))
{
	echo " This server is compatible!\n";
}
else
{
	die(" The server uses an unsupported protocol version: {$protocolVersion}\n");
}

if($acknowledgements)
{
	echo "\nPress enter to acknowledge the following and connect:\n";
	foreach($acknowledgements as $acknowledgement)
	{
		echo "- {$acknowledgement}\n";
	}
	fgets($stdin);
}

echo "Connecting using ".$supportedVersions[$protocolVersion]." ({$protocolVersion})..";
$packet_ids = [
	// Clientbound
	"chat_message" => [0x0E, 0x0F, 0x0F, 0x0F, 0x0F, 0x02],
	"disconnect" => [0x1B, 0x1A, 0x1A, 0x1A, 0x1A, 0x40],
	"keep_alive_request" => [0x21, 0x1F, 0x1F, 0x1F, 0x1F, 0x00],
	"join_game" => [0x25, 0x23, 0x23, 0x23, 0x23, 0x01],
	"player_list_item" => [0x30, 0x2E, 0x2D, 0x2D, 0x2D, 0x38],
	"player_position_and_look" => [0x32, 0x2F, 0x2E, 0x2E, 0x2E, 0x08],
	// Serverbound
	"send_chat_message" => [0x02, 0x02, 0x03, 0x02, 0x02, 0x01],
	"client_settings" => [0x04, 0x04, 0x05, 0x04, 0x04, 0x15],
	"send_plugin_message" => [0x0A, 0x09, 0x0A, 0x09, 0x09, 0x17],
	"keep_alive_response" => [0x0E, 0x0B, 0x0C, 0x0B, 0x0B, 0x00],
	"held_item_change" => [0x21, 0x1A, 0x1A, 0x17, 0x17, 0x09]
];
foreach($packet_ids as $n => $v)
{
	if($protocolVersion >= 393)
	{
		$packet_ids[$n] = $v[0];
	}
	else if($protocolVersion >= 336)
	{
		$packet_ids[$n] = $v[1];
	}
	else if($protocolVersion >= 328)
	{
		$packet_ids[$n] = $v[2];
	}
	else if($protocolVersion >= 314)
	{
		$packet_ids[$n] = $v[3];
	}
	else if($protocolVersion >= 107)
	{
		$packet_ids[$n] = $v[4];
	}
	else
	{
		$packet_ids[$n] = $v[5];
	}
}
echo ".";
connect($protocolVersion, 0x02);
echo " Connection established.\n";
writeVarInt(0x00);
writeString($name);
sendPacket();
function mcsha1($str)
{
	$gmp = gmp_import(sha1($str, true));
	if(gmp_cmp($gmp, gmp_init("0x8000000000000000000000000000000000000000")) >= 0)
	{
		$gmp = gmp_mul(gmp_add(gmp_xor($gmp, gmp_init("0xFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")), gmp_init(1)), gmp_init(-1));
	}
	return gmp_strval($gmp, 16);
}
echo "Logging in...";
do
{
	$id = readPacket();
	if($id == 0x04) // Login Plugin Request
	{
		writeVarInt(0x02); // Login Plugin Response
		writeVarInt(readVarInt());
		writeBoolean(false);
		sendPacket();
	}
	else if($id == 0x03) // Set Compression
	{
		$compression_threshold = readVarInt();
		if($compression_threshold < 0)
		{
			$compression_threshold = 0;
		}
	}
	else if($id == 0x02) // Login Success
	{
		$uuid = readString(36);
		$name_ = readString(16);
		if($name != $name_)
		{
			die("Server did not accept name: '{$name}' != '{$name_}'\n");
		}
		echo " Success!\n";
		break;
	}
	else if($id == 0x01) // Encryption Request
	{
		if(!$online)
		{
			die(" This server requires a Minecraft account to join.\n");
		}
		$server_id = readString(20);
		$publicKey = readString();
		$verify_token = readString();
		$shared_secret = "";
		for($i = 0; $i < 16; $i++)
		{
			$shared_secret .= chr(rand(0, 255));
		}
		if(httpPOST("https://sessionserver.mojang.com/session/minecraft/join", [
			"accessToken" => $profiles["authenticationDatabase"][$profiles["selectedUser"]["account"]]["accessToken"],
			"selectedProfile" => $profiles["selectedUser"]["profile"],
			"serverId" => mcsha1($server_id.$shared_secret.$publicKey)
		]) === false)
		{
			die(" The session server is down for maintenance.\n");
		}
		$publicKey = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".base64_encode($publicKey)."\n-----END PUBLIC KEY-----");
		writeVarInt(0x01); // Encryption Response
		$crypted = "";
		openssl_public_encrypt($shared_secret, $crypted, $publicKey, OPENSSL_PKCS1_PADDING);
		writeString($crypted);
		openssl_public_encrypt($verify_token, $crypted, $publicKey, OPENSSL_PKCS1_PADDING);
		writeString($crypted);
		sendPacket();
		$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
		stream_filter_append($stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
		stream_filter_append($stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
	}
	else if($id == 0x00) // Disconnect
	{
		die(" ".chatToANSIText(readChat())."\n");
	}
	else
	{
		die(" Unexpected response: {$id} {$read_buffer}\n");
	}
}
while(true);

stream_set_blocking($stdin, false);
$joined = false;
$players = [];
do
{
	$start = microtime(true);
	// We can't use stream_select on a filtered stream and we'd also like to observe STDIN so we have to poll.
	while(($id = readPacket(false)) !== false)
	{
		//echo "Received Packet ID {$id}\n";
		if($id == $packet_ids["chat_message"])
		{
			echo chatToANSIText(readChat())."\n";
		}
		else if($id == $packet_ids["player_list_item"])
		{
			$action = readVarInt();
			$amount = readVarInt();
			for($i = 0; $i < $amount; $i++)
			{
				$uuid = readUUIDBytes();
				if($action == 0)
				{
					$name = readString();
					$properties = readVarInt();
					for($j = 0; $j < $properties; $j++)
					{
						readString();
						readString();
						if(readBoolean())
						{
							readString();
						}
					}
					$gamemode = readVarInt();
					$ping = readVarInt();
					$players[$uuid] = [
						"name" => $name,
						"gamemode" => $gamemode,
						"ping" => $ping
					];
				}
				else if($action == 1)
				{
					if(isset($players[$uuid]))
					{
						$players[$uuid]["gamemode"] = readVarInt();
					}
				}
				else if($action == 2)
				{
					if(isset($players[$uuid]))
					{
						$players[$uuid]["ping"] = readVarInt();
					}
				}
				else if($action == 4)
				{
					unset($players[$uuid]);
				}
			}
		}
		else if($id == $packet_ids["keep_alive_request"])
		{
			startPacket("keep_alive_response");
			if($protocolVersion >= 339)
			{
				writeLong(readLong());
			}
			else
			{
				writeVarInt(readVarInt());
			}
			sendPacket();
		}
		else if($id == $packet_ids["player_position_and_look"])
		{
			if(!$joined)
			{
				if(isset($options["joinmsg"]))
				{
					echo $options["joinmsg"]."\n";
					startPacket("send_chat_message");
					writeString($options["joinmsg"]);
					sendPacket();
				}
				$joined = true;
			}
		}
		else if($id == $packet_ids["join_game"])
		{
			startPacket("send_plugin_message");
			writeString($protocolVersion > 340 ? "minecraft:brand" : "MC|Brand");
			writeString("php-minecraft-client");
			sendPacket();
			startPacket("client_settings");
			writeString(isset($options["locale"]) ? $options["locale"] : "en_US");
			writeByte(2); // View Distance
			writeVarInt(0); // Chat Mode (0 = all)
			writeBoolean(true); // Chat colors
			writeByte(0x7F); // Displayed Skin Parts (7F = all)
			if($protocolVersion > 47)
			{
				writeVarInt(1); // Main Hand (0 = left, 1 = right)
			}
			sendPacket();
		}
		else if($id == $packet_ids["disconnect"])
		{
			die("Server closed connection: ".chatToANSIText(readChat())."\n");
		}
	}
	$streams = [$stdin];
	$null = null;
	if(stream_select($streams, $null, $null, 0) > 0)
	{
		$msg = trim(fgets($stdin));
		$send = true;
		if(substr($msg, 0, 2) == "..")
		{
			$msg = substr($msg, 1);
		}
		else if(substr($msg, 0, 1) == ".")
		{
			$send = false;
			$msg = substr($msg, 1);
			$args = explode(" ", $msg);
			switch($args[0])
			{
				case "?":
				case "help":
				echo "Yay! You found commands, which start with a period.\n";
				echo "If you want to send a message starting with a period, use two.\n";
				echo "?, help     shows this help\n";
				echo "list        lists all players in the player list\n";
				echo "slot <1-9>  sets selected hotbar slot\n";
				break;

				case "list":
				$gamemodes = [
					0 => "Survival",
					1 => "Creative",
					2 => "Adventure",
					3 => "Spectator"
				];
				foreach($players as $player)
				{
					echo $player["name"].str_repeat(" ", 17 - strlen($player["name"])).str_repeat(" ", 5 - strlen($player["ping"])).$player["ping"]." ms  ".$gamemodes[$player["gamemode"]]." Mode\n";
				}
				break;

				case "slot";
				$slot = 0;
				if(isset($args[1]))
				{
					$slot = intval($args[1]);
				}
				if($slot < 1 || $slot > 9)
				{
					echo "\033[91mSyntax: .slot <1-9>\033[0;97;40m\n";
				}
				startPacket("held_item_change");
				writeShort($slot - 1);
				sendPacket();
				break;

				default:
				echo "\033[91mUnknown command '.{$msg}' -- use '.help' for a list of commands.\033[0;97;40m\n";
			}
		}
		if($send)
		{
			startPacket("send_chat_message");
			writeString($msg);
			sendPacket();
		}
	}
	$elapsed = (microtime(true) - $start);
	if(($remaining = (0.02 - $elapsed)) > 0) // Make sure we've waited at least 0.02 seconds (20 ms) before going again because polling
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while(true);
