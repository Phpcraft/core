<?php
require "vendor/autoload.php";
use Phpcraft\Phpcraft;
echo "Phpcraft Cache Utility\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php cache.php`.\n");
}
if(!Phpcraft::$json_cache->data)
{
	die("Nothing's cached.\n");
}
switch(@$argv[1])
{
	case "list":
		$has_expired = false;
		echo count(Phpcraft::$json_cache->data)." cache entries:\n";
		foreach(Phpcraft::$json_cache->data as $url => $entry)
		{
			echo $url." — ";
			$time_til_expire = $entry["expiry"] - time();
			if($time_til_expire <= 0)
			{
				echo "expired\n";
				$has_expired = true;
			}
			else
			{
				echo "expires in ";
				if($time_til_expire > 86400)
				{
					$days = floor($time_til_expire / 86400);
					echo $days."d";
					$time_til_expire -= ($days * 86400);
				}
				if($time_til_expire > 3600)
				{
					$hours = floor($time_til_expire / 3600);
					echo $hours."h";
					$time_til_expire -= ($hours * 3600);
				}
				if($time_til_expire > 60)
				{
					$mins = floor($time_til_expire / 60);
					echo $mins."m";
					$time_til_expire -= ($mins * 60);
				}
				echo $time_til_expire."s\n";
			}
		}
		if($has_expired)
		{
			echo "Run `php cache.php maintain` to remove expired entries.\n";
		}
		break;
	case "maintain":
		echo "Cache entries — before: ".count(Phpcraft::$json_cache->data)."\n";
		Phpcraft::maintainCache();
		if(file_exists("src/.json_cache"))
		{
			echo "Cache entries — after: ".count(Phpcraft::$json_cache->data)."\n";
		}
		else
		{
			echo "Cache entries — after: 0\n";
		}
		break;
	case "purge":
		echo "Cache entries — before: ".count(Phpcraft::$json_cache->data)."\n";
		Phpcraft::$json_cache->data = [];
		Phpcraft::$json_cache->save();
		echo "Cache entries — after: 0\n";
		break;
	default:
		echo "Usage: php cache.php <list|maintain|purge>\n";
}
