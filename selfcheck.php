<?php
echo "Phpcraft Self Check\nhttps://github.com/timmyrs/Phpcraft\n\n";
if(version_compare(PHP_VERSION, "7.0", "<"))
{
	die("Phpcraft requires PHP 7.0 or above.\n");
}
foreach([
	"SPL",
	"json",
	"zlib"
] as $ext)
{
	if(!extension_loaded($ext))
	{
		die("The {$ext} extension is required.\n");
	}
}
$apt = [];
$pecl = [];
if(extension_loaded("mbstring"))
{
	echo "./";
}
else
{
	echo "X";
	array_push($apt, "php-mbstring");
}
echo " mbstring\n";
if(extension_loaded("gmp"))
{
	echo "./";
}
else
{
	echo "X";
	array_push($apt, "php-gmp");
}
echo " GMP\n";
if(extension_loaded("zlib"))
{
	echo "./";
}
else
{
	echo "X";
}
echo " zlib\n";
if(extension_loaded("sockets"))
{
	echo "./";
}
else
{
	echo "X";
}
echo " Sockets extension (required only for LanInterface)\n\n";
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
echo " mcrypt (optional for more performance)\n\n";
if(!empty($apt) || !empty($pecl) || !extension_loaded("zlib"))
{
	echo "Check the extensions section of your php.ini.\n";
	if(!defined("PHP_WINDOWS_VERSION_MAJOR"))
	{
		echo "You might be able to install all missing extensions by running:\n";
		if(!empty($apt))
		{
			echo "sudo apt-get -y install ".join(" ", $apt)."\n";
		}
		if(!empty($pecl))
		{
			echo "sudo pecl install ".join(" ", $pecl)."\n";
		}
	}
}
