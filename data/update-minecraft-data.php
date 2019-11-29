<?php
function recursivelyUpdate($dir)
{
	foreach(scandir($dir) as $file)
	{
		if(in_array($file, [".", ".."]))
		{
			continue;
		}
		if(is_dir($dir.$file))
		{
			recursivelyUpdate($dir.$file."/");
		}
		else if(substr($file, -5) == ".json")
		{
			file_put_contents($dir.$file, json_encode(json_decode(file_get_contents("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/".substr($dir, 15).$file)), JSON_UNESCAPED_SLASHES));
		}
	}
}
recursivelyUpdate("minecraft-data/");
