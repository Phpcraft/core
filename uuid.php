<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: php uuid.php <uuid>\n");
}
$uuid = new \Phpcraft\UUID($argv[1]);
echo "With Dashes: ".$uuid->toString(true)."\nWithout Dashes: ".$uuid->toString()."\nSkin Type: ".($uuid->isSlim() ? "Alex" : "Steve")."\n";
