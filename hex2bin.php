<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: php hex2bin.php <file>");
}
$cont = hex2bin(str_replace([" ", "\r", "\n", "\t"], "", file_get_contents($argv[1])));
file_put_contents($argv[1].".bin", $cont);
