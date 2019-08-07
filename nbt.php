<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: nbt.php <file>\n");
}
use Phpcraft\Connection;
$con = new Connection();
$con->read_buffer = file_get_contents($argv[1]);
$tag = $con->readNBT();
assert($con->read_buffer === "");
echo "::: String Dump\n";
echo $tag->__toString()."\n";
echo "::: SNBT\n";
echo $tag->toSNBT(false)."\n";
echo "::: Pretty SNBT\n";
echo $tag->toSNBT(true)."\n";
