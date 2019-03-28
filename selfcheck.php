<?php
echo "Phpcraft Self Check\nhttps://github.com/timmyrs/Phpcraft\n\n";
if(version_compare(PHP_VERSION, "7.0", "<"))
{
	die("Phpcraft requires PHP 7.0 or above.\n");
}
if(!file_exists("vendor/autoload.php"))
{
	die("Please run `composer install --no-dev` first.\n");
}
require "vendor/autoload.php";
if(file_exists(__DIR__."/src/.cache"))
{
	$before = count(json_decode(file_get_contents("src/.cache"), true));
	\Phpcraft\Phpcraft::maintainCache();
	if(file_exists(__DIR__."/src/.cache"))
	{
		$after = count(json_decode(file_get_contents("src/.cache"), true));
	}
	else
	{
		$after = 0;
	}
	if($after == $before)
	{
		echo "(i) {$after} cache entries\n\n";
	}
	else
	{
		echo "(i) Removed ".($after - $before)." outdated cache entries; {$after} remain\n\n";
	}
}
else
{
	echo "(i) 0 cache entries\n\n";
}

$apt = [];
$pecl = [];

if(extension_loaded("gmp"))
{
	echo "./";
}
else
{
	echo "X";
	array_push($apt, "php-gmp");
}
echo " Full functionality of Connection and NbtTag\n  ";
if(in_array("php-gmp", $apt))
{
	echo "X";
}
else
{
	echo "./";
}
echo " GMP\n\n";

if(extension_loaded("openssl") && extension_loaded("curl") && extension_loaded("mcrypt"))
{
	echo "./";
}
else
{
	echo "X";
	if(!extension_loaded("openssl"))
	{
		array_push($apt, "openssl");
	}
	if(!extension_loaded("curl"))
	{
		array_push($apt, "php-curl");
	}
	if(!extension_loaded("mcrypt"))
	{
		if(version_compare(PHP_VERSION, "7.2", "<"))
		{
			array_push($apt, "php-mcrypt");
		}
		else
		{
			array_push($apt, "php-dev gcc make autoconf libc-dev pkg-config libmcrypt-dev php-pear");
			array_push($pecl, "mcrypt-1.0.1");
		}
	}
}
echo " \"Online mode\" functionality\n  ";
if(in_array("openssl", $apt))
{
	echo "X";
}
else
{
	echo "./";
}
echo " OpenSSL\n  ";
if(in_array("php-curl", $apt))
{
	echo "X";
}
else
{
	echo "./";
}
echo " cURL\n  ";
if(extension_loaded("mcrypt"))
{
	echo "./";
}
else
{
	echo "X";
}
echo " mcrypt\n\n";

if(!empty($apt) || !empty($pecl))
{
	echo "\nTo install all missing dependencies, run:\n";
	if(!empty($apt))
	{
		echo "sudo apt-get -y install ".join(" ", $apt)."\n";
	}
	if(!empty($pecl))
	{
		echo "sudo pecl install ".join(" ", $pecl)."\n";
	}
}
