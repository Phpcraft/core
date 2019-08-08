<?php
require "vendor/autoload.php";
use Phpcraft\Phpcraft;
if(empty($argv[1]))
{
	$in = Phpcraft::isWindows() ? "" : file_get_contents("php://stdin");
	if($in === "")
	{
		echo "Syntax: php bin2hex.php <file>\n";
		if(!Phpcraft::isWindows())
		{
			echo "or: echo \"...\" | php bin2hex.php\n";
		}
		exit;
	}
}
else
{
	$in = file_get_contents($argv[1]);
}
echo preg_replace("/(.{2})/", "$1 ", bin2hex($in));
