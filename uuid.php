<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: php uuid.php <uuid>\n");
}
$uuid = \Phpcraft\UUID::fromString($argv[1]);
echo "With Dashes: ".$uuid->toString(true)."\nWithout Dashes: ".$uuid->toString()."\nSkin Type: ".($uuid->isSlim() ? "Alex" : "Steve")."\n";
