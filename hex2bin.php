<?php
require "vendor/autoload.php";
use Phpcraft\Phpcraft;
if(empty($argv[1]))
{
	$in = "";
	if(!Phpcraft::isWindows())
	{
		$fh = fopen("php://stdin", "r");
		stream_set_blocking($fh, false);
		$in = stream_get_contents($fh);
	}
	if($in === "")
	{
		echo "Syntax: php hex2bin.php <file>\n";
		if(!Phpcraft::isWindows())
		{
			echo "or: echo \"...\" | php hex2bin.php\n";
		}
		exit;
	}
}
else
{
	$in = file_get_contents($argv[1]);
}
echo hex2bin(str_replace([
	" ",
	"\r",
	"\n",
	"\t"
], "", $in));
