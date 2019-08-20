<?php
require "vendor/autoload.php";
use Phpcraft\
{Connection, Phpcraft};
$con = new Connection();
if(empty($argv[1]))
{
	$con->read_buffer = "";
	if(!Phpcraft::isWindows())
	{
		$fh = fopen("php://stdin", "r");
		stream_set_blocking($fh, false);
		$con->read_buffer = stream_get_contents($fh);
	}
	if($con->read_buffer === "")
	{
		echo "Syntax: php nbt.php <file>\n";
		if(!Phpcraft::isWindows())
		{
			echo "or: echo \"...\" | php nbt.php\n";
		}
		exit;
	}
}
else
{
	$con->read_buffer = file_get_contents($argv[1]);
}
/** @noinspection PhpUnhandledExceptionInspection */
$tag = $con->readNBT();
if($con->read_buffer !== "")
{
	$bytes = strlen($con->read_buffer);
	echo "Warning: NBT has been read, but {$bytes} byte".($bytes == 1 ? "" : "s")." remain".($bytes == 1 ? "s" : "").": ".bin2hex($con->read_buffer)."\n";
}
echo "::: String Dump\n";
echo $tag->__toString()."\n";
echo "::: SNBT\n";
echo $tag->toSNBT(false)."\n";
echo "::: Pretty SNBT\n";
echo $tag->toSNBT(true)."\n";
