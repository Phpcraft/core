<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: snbt.php <snbt>\n");
}
use Phpcraft\
{Connection, Nbt\NbtTag};
$tag = NbtTag::fromSNBT(join(" ", array_slice($argv, 1)));
echo "::: Pretty SNBT\n";
echo $tag->toSNBT(true)."\n";
echo "::: String Dump\n";
echo $tag->__toString()."\n";
echo "::: NBT Hex\n";
$con = new Connection(-1);
$tag->write($con);
echo preg_replace("/(.{2})/", "$1 ", bin2hex($con->write_buffer))."\n";
