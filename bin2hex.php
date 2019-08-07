<?php
require "vendor/autoload.php";
if(empty($argv[1]))
{
	die("Syntax: php bin2hex.php <file>");
}
$cont = preg_replace("/(.{2})/", "$1 ",bin2hex(file_get_contents($argv[1])));
file_put_contents($argv[1].".hex", $cont);
