<?php
function recursivelyMinimize($dir)
{
	foreach(scandir($dir) as $file)
	{
		if(in_array($file, [".", ".."]))
		{
			continue;
		}
		if(is_dir($dir.$file))
		{
			recursivelyMinimize($dir.$file."/");
		}
		else if(substr($file, -5) == ".json")
		{
			file_put_contents($dir.$file, json_encode(json_decode(file_get_contents($dir.$file)), JSON_UNESCAPED_SLASHES));
		}
	}
}
recursivelyMinimize("./");
