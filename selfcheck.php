<?php
echo "Phpcraft Self Check\n";
if(version_compare(PHP_VERSION, "7.0", "<"))
{
	die("Phpcraft requires PHP 7.0 or above.\n");
}
if(!extension_loaded("SPL"))
{
	die("How is SPL not loaded?!\n");
}
$i = 0;
$i_limit = 2;
foreach([
	"Basic" => [
		"gmp" => false,
		"json" => false,
		"mbstring" => false,
		"zlib" => false
	],
	'"Online mode"' => [
		"openssl" => false,
		"curl" => false,
		"mcrypt" => "more performance"
	]
] as $feature => $exts)
{
	echo (++$i == $i_limit ? "└ " : "├ ").$feature." functionality ";
	$ok = true;
	$str = "";
	$j = 0;
	$j_limit = count($exts);
	foreach($exts as $ext => $optional_for)
	{
		$str .= ($i == $i_limit ? "  " : "│ ").(++$j == $j_limit ? "└" : "├")." ".$ext." ";
		if($optional_for)
		{
			$str .= "(optional for ".$optional_for.") ";
		}
		if(extension_loaded($ext))
		{
			$str .= "✓\n";
		}
		else
		{
			$ok = false;
			$str .= "\n".($i == $i_limit ? "  " : "│ ").($j == $j_limit ? " " : "│")." └ ";
			if(defined("PHP_WINDOWS_VERSION_MAJOR"))
			{
				$str .= "Check the extensions section of your php.ini.\n";
			}
			else if($ext == "mcrypt" && version_compare(PHP_VERSION, "7.2") >= 0)
			{
				$str .= "sudo apt-get -y install php-dev gcc make autoconf libc-dev pkg-config libmcrypt-dev php-pear && sudo pecl install mcrypt-1.0.1\n";
			}
			else
			{
				$str .= "sudo apt-get -y install php-".$ext."\n";
			}
		}
	}
	if($ok)
	{
		echo "✓";
	}
	echo "\n".$str;
}
