<?php
require __DIR__."/src/autoload.php";
echo "PHP Minecraft Server List Pinger\nhttps://github.com/timmyrs/Phpcraft\n";
if(empty($argv[1]))
{
	die("Syntax: ".$argv[0]." <ip[:port]>\n");
}
echo "Resolving...";
$server = \Phpcraft\Phpcraft::resolve($argv[1]);
$serverarr = explode(":", $server);
if(count($serverarr) != 2)
{
	die(" Failed to resolve name. Got {$server}\n");
}
echo " Connecting to {$server}...";
$con = new \Phpcraft\ServerStatusConnection($serverarr[0], $serverarr[1]);
echo " Getting status...";
$info = $con->getStatus();
echo "\n\n";
if(isset($info["description"]))
{
	echo \Phpcraft\Phpcraft::chatToANSIText($info["description"])."\n\n";
}
else
{
	echo "This server has no description/MOTD.\n";
}
if(isset($info["version"]) && isset($info["version"]["protocol"]))
{
	if($minecraft_versions = \Phpcraft\Phpcraft::getMinecraftVersionsFromProtocolVersion($info["version"]["protocol"]))
	{
		if(isset($info["version"]["name"]))
		{
			echo "This server is running a Phpcraft-compatible ".$info["version"]["name"]." (".$minecraft_versions[0].") server.\n";
		}
		else
		{
			echo "This server is running a Phpcraft-compatible ".$minecraft_versions[0]." server.\n";
		}
	}
	else
	{
		if(isset($info["version"]["name"]))
		{
			echo "This server is running a Phpcraft-incompatible ".$info["version"]["name"]." server.\n";
		}
		else
		{
			echo "This server is running a Phpcraft-incompatible version.\n";
		}
	}
}
if(isset($info["players"]))
{
	$sample = "";
	if(isset($info["players"]["sample"]))
	{
		foreach($info["players"]["sample"] as $player)
		{
			if(isset($player["name"]))
			{
				$sample .= "- ".$player["name"]."\n";
			}
		}
	}
	echo "There are ".(isset($info["players"]["online"])?$info["players"]["online"]:"???")."/".(isset($info["players"]["max"])?$info["players"]["max"]:"???")." players online".(($sample=="")?".\n":":\n".$sample);
}
echo "The server answered the status request within ".round($info["ping"] * 1000)." ms.\n";
