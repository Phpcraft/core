<?php
echo "\033[0;97;40mPHP Minecraft Client 1.0\n";
$stdin = fopen("php://stdin", "r");
stream_set_blocking($stdin, true);

$options = [];

for($i = 1; $i < count($argv); $i++)
{
	$arg = $argv[$i];
	while(substr($arg, 0, 1) == "-")
	{
		$arg = substr($arg, 1);
	}
	$n;
	$v;
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
		case "name":
		case "server":
		case "langfile":
		case "joinmsg":
		$options[$n] = $v;
		break;

		case "help":
		echo "\n\"name=<name>\" -- skip name input and use <name> as name\n";
		echo "\"server=<server>\" -- skip server input and connect to <server>\n";
		echo "\"langfile=<file>\" -- load Minecraft translations from <file>\n";
		echo "\"joinmsg=<msg>\" -- will send <msg> as soon as we are connected\n";
		exit;
		
		default:
		echo "\nUnknown argument '{$n}' -- try 'help' for a list of arguments.\n";
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
		echo "Language file ".$options["langfile"]." doesn't exist. Expect broken messages.\n";
	}
	$translations = json_decode(file_get_contents($options["langfile"]), true);
}
else
{
	echo "No language file has been provided. Expect broken messages.\n";
}

$name;
if(isset($options["name"]))
{
	$name = $options["name"];
}
while(!$name)
{
	echo "\nHow would you like to be called in-game? ";
	$name = trim(fgets($stdin));
}

function resolveName($server, $withPort = true)
{
	if(ip2long($server) !== false) // No need to resolve IPs.
	{
		return $server.($withPort ? ":25565" : "");
	}
	if($server == "localhost") // For whatever reason dns_get_record doesn't resolve localhost.
	{
		return "127.0.0.1".($withPort ? ":25565" : "");
	}
	if($res = dns_get_record("_minecraft._tcp.{$server}", DNS_SRV))
	{
		$i = array_rand($res);
		return resolveName($res[$i]["target"], false).":".$res[$i]["port"];
	}
	return $server.($withPort ? ":25565" : "");
}
$server;
if(isset($options["server"]))
{
	$server = $options["server"];
}
while(!$server)
{
	echo "\nWhat server would you like to join? ";
	$server = trim(fgets($stdin));
}
echo "\nResolving...";
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
$socket = fsockopen($serverarr[0], $serverarr[1], $errno, $errstr, 10) or die($errstr."\n");
$compression_threshold = false;

$write_buffer = "";
function intToVarInt($value)
{
	$bytes = "";
	global $write_buffer;
	do
	{
		$temp = ($value & 0b01111111);
		$value = $value >> 7;
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
function sendPacket()
{
	global $write_buffer, $socket, $compression_threshold;
	$length = strlen($write_buffer);
	if($compression_threshold)
	{
		if($length >= $compression_threshold)
		{
			$compressed = gzcompress($write_buffer, 1);
			$compressed_length_varint = intToVarInt(strlen($compressed));
			$length += strlen($compressed_length_varint);
			fwrite($socket, intToVarInt($length).$compressed_length_varint.$compressed);
		}
		else
		{
			fwrite($socket, intToVarInt($length + 1)."\x00".$write_buffer);
		}
	}
	else
	{
		fwrite($socket, intToVarInt($length).$write_buffer);
	}
	$write_buffer = "";
}
$read_buffer = "";
function readPacket()
{
	global $read_buffer, $socket, $compression_threshold;
	$length = 0;
	$read = 0;
	do
	{
		$byte = fgetc($socket);
		while($byte === false)
		{
			$byte = fgetc($socket);
		}
		$byte = ord($byte);
		$length |= ($byte & 0x7F) << $read++ * 7;
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
	$read_buffer = fread($socket, $length);
	while(strlen($read_buffer) < $length)
	{
		$read_buffer .= fread($socket, $length - strlen($read_buffer));
	}
	if($compression_threshold)
	{
		$uncompressed_length = readVarInt();
		if($uncompressed_length > 0)
		{
			$read_buffer = gzuncompress($read_buffer, $uncompressed_length);
		}
	}
	return readVarInt();
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
		$value |= ($byte & 0x7F) << $read++ * 7;
		if($read > 5)
		{
			throw new Exception("VarInt is too big\n");
		}
		if(($byte & 0x80) != 128 )
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
	if($length > ($maxLength * 4) + 3)
	{
		throw new Exception("String length {$length} exceeds maximum of {$length}");
	}
	if($length > strlen($read_buffer))
	{
		throw new Exception("Not enough bytes to read string with length {$length}");
	}
	$str = substr($read_buffer, 0, $length);
	$read_buffer = substr($read_buffer, $length);
	return $str;
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
	$child;
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
	}
	return $text;
}

writeVarInt(0x00);
writeVarInt(401); // TODO: We're currently using 1.13.1 but some protocol hacking might be optimal.
writeString($serverarr[0]);
writeShort($serverarr[1]);
writeVarInt(0x02);
sendPacket();
writeVarInt(0x00);
writeString($name);
sendPacket();
$id = readPacket();
if($id == 0x03)
{
	$compression_threshold = readVarInt();
	if($compression_threshold < 0)
	{
		$compression_threshold = 0;
	}
	$id = readPacket();
}
if($id != 0x02)
{
	die("Unexpected Response: {$id} {$read_buffer}\n");
}

stream_set_blocking($stdin, false);
$joined = false;
do
{
	$sockets = [$socket, $stdin];
	$null = null;
	$num_changed_streams = stream_select($sockets, $null, $null, null);
	if($num_changed_streams === false)
	{
		die("An error occured whilst watching streams.\n");
	}
	if($num_changed_streams > 0)
	{
		if(in_array($socket, $sockets))
		{
			$id = readPacket();
			//echo "Received Packet ID {$id}\n";
			if($id == 0x0E) // Chat Message
			{
				echo chatToANSIText(readChat())."\n";
			}
			else if($id == 0x19) // Plugin Message
			{
				$channel = readString(20);
				if($channel == "minecraft:brand")
				{
					echo "This is a ".readString()." server.\n";
					writeVarInt(0x0A);
					writeString("minecraft:brand");
					writeString("php-minecraft-server");
					sendPacket();
				}
				else
				{
					echo "Unhandled Plugin Message: {$channel} {$read_buffer}\n";
				}
			}
			else if($id == 0x1B) // Disconnect
			{
				die("Server closed connection: ".chatToANSIText(readChat())."\n");
			}
			else if($id == 0x21) // Keep Alive
			{
				writeVarInt(0x0E);
				writeLong(readLong());
				sendPacket();
			}
			else if($id == 0x32) // Player Position And Look
			{
				if(!$joined)
				{
					if(isset($options["joinmsg"]))
					{
						echo $options["joinmsg"]."\n";
						writeVarInt(0x02);
						writeString($options["joinmsg"]);
						sendPacket();
					}
					$joined = true;
				}
			}
		}
		if(in_array($stdin, $sockets))
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
					case ".help":
					echo "Yay, you found commands, which start with a period!\n";
					echo "If you want to send a message starting with a period, use two.\n";
					echo ".help -- shows this help\n";
					echo ".slot <1-9> -- sets selected hotbar slot\n";
					break;

					case ".slot";
					$slot = 0;
					if(isset($args[1]))
					{
						$slot = intval($args[1]);
					}
					if($slot < 1 || $slot > 9)
					{
						echo "\033[91mSyntax: .slot <1-9>\033[0;97;40m\n";
					}
					writeVarInt(0x21);
					writeShort($slot - 1);
					sendPacket();
					break;

					default:
					echo "\033[91mUnknown command '.{$msg}' -- use '.help' for a list of commands.\033[0;97;40m\n";
				}
			}
			if($send)
			{
				writeVarInt(0x02);
				writeString($msg);
				sendPacket();
			}
		}
	}
}
while(true);
