<?php
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php packets.php`.\n");
}
require "vendor/autoload.php";
echo "PHP Minecraft Packet Dump Reader\nhttps://github.com/timmyrs/Phpcraft\n";
if(empty($argv[1]) || empty($argv[2]))
{
	die("Syntax: ".$argv[0]." <recipient: client or server> <dump file>\n");
}
if(!in_array($argv[1], ["client", "server"]))
{
	die("Invalid recipient '".$argv[1]."', expected 'client' or 'server'.\n");
}
$fh = fopen($argv[2], "r");
$con = new \Phpcraft\Connection(-1, $fh);
if(!($pv = $con->readPacket()) || strlen($con->read_buffer) > 0)
{
	die("Failed to read protocol version.\nWrite 0x05 0xff 0xff 0xff 0xff 0x0f to the beginning of the file so protocol version -1 is detected.\n");
}
if($mcversions = \Phpcraft\Phpcraft::getMinecraftVersionRangeFromProtocolVersion($pv))
{
	echo "Detected Minecraft {$mcversions} (protocol version {$pv}).\n";
}
else
{
	echo "Detected unsupported protocol version {$pv}.\n";
}
function processBatch()
{
	global $id_count, $last_id, $last_name, $total_size;
	if($id_count == 1)
	{
		echo "1x ".convertPacket($last_id, $last_name)." with {$total_size} B of data\n";
	}
	else
	{
		echo $id_count."x ".convertPacket($last_id, $last_name)." with {$total_size} B (avg. ".round($total_size / $id_count)." B) of data\n";
	}
}
function convertPacket($id, $name)
{
	if($name)
	{
		return $name." (0x".dechex($id)." | {$id})";
	}
	else
	{
		return "0x".dechex($id)." ({$id})";
	}
}
$con->protocol_version = $pv;
$last_id = null;
$last_name = "";
$id_count = 0;
$total_size = 0;
while($id = $con->readPacket())
{
	$size = strlen($con->read_buffer);
	if($argv[1] == "client")
	{
		$packetId = \Phpcraft\ClientboundPacket::getById($id, $pv);
	}
	else
	{
		$packetId = \Phpcraft\ServerboundPacket::getById($id, $pv);
	}
	if($packetId)
	{
		$name = $packetId->name;
	}
	else
	{
		$name = null;
	}
	if($size == 0)
	{
		die(convertPacket($id, $name)." has no data.\n");
	}
	if($packetId && ($packet = $packetId->/** @scrutinizer ignore-call */init($con)))
	{
		if($last_id)
		{
			processBatch();
			$last_id = false;
			$id_count = 0;
			$total_size = 0;
		}
		echo $packet->toString()."\n";
	}
	else if($last_id === $id)
	{
		$id_count++;
		$total_size += $size;
	}
	else
	{
		if($last_id)
		{
			processBatch();
		}
		$last_id = $id;
		$last_name = $name;
		$id_count = 1;
		$total_size = $size;
	}
}
if($last_id)
{
	processBatch();
}
if(strlen(stream_get_contents($fh)) > 0)
{
	echo "Error: There was still some data left in the stream despite having finished reading.\n";
}
else
{
	echo "This seems to have been a valid packet dump file.\n";
}
fclose($fh);
