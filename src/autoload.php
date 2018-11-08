<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
foreach(scandir(__DIR__) as $file)
{
	if(substr($file, -10) == ".class.php")
	{
		require_once __DIR__."/".$file;
	}
}
