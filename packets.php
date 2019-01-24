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
function convertPacket($id)
{
	global $argv, $pv;
	if($argv[1] == "client")
	{
		$packet_name = \Phpcraft\Packet::clientboundPacketIdToName($id, $pv);
	}
	else
	{
		$packet_name = \Phpcraft\Packet::serverboundPacketIdToName($id, $pv);
	}
	if($packet_name)
	{
		return $packet_name." (0x".dechex($id)." | {$id})";
	}
	else
	{
		return "0x".dechex($id)." ({$id})";
	}
}
$con = new \Phpcraft\Connection($pv, $fh);
$last_id = null;
$id_count;
$total_size = 0;
while($id = $con->readPacket())
{
	$size = strlen($con->read_buffer);
	if($size == 0)
	{
		die(convertPacket($last_id)." has no data.\n");
	}
	if($last_id === $id)
	{
		$id_count++;
		$total_size += $size;
	}
	else
	{
		if($last_id)
		{
			if($id_count == 1)
			{
				echo $id_count."x ".convertPacket($last_id)." with {$total_size} B of data\n";
			}
			else
			{
				echo $id_count."x ".convertPacket($last_id)." with {$total_size} B (avg. ".round($total_size / $id_count)." B) of data\n";
			}
		}
		$last_id = $id;
		$id_count = 1;
		$total_size = $size;
	}
}
if($last_id)
{
	if($id_count == 1)
	{
		echo $id_count."x ".convertPacket($last_id)." with {$total_size} B of data\n";
	}
	else
	{
		echo $id_count."x ".convertPacket($last_id)." with {$total_size} B (avg. ".round($total_size / $id_count)." B) of data\n";
	}
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
