<?php
require "vendor/autoload.php";
use hellsh\UUID;
if(empty($argv[1]))
{
	die("Syntax: php uuid.php <uuid>\n");
}
$uuid = new UUID($argv[1]);
echo "With Dashes: ".$uuid->toString(true)."\nWithout Dashes: ".$uuid->toString(false)."\nSkin Type: ".($uuid->hashCode() & 1 ? "Alex" : "Steve")."\n";
