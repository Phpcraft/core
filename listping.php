<?php
echo "\033[0;97;40mMinecraft Server List Ping\nhttps://github.com/timmyrs/Phpcraft\n";
require __DIR__."/Phpcraft.php";
if(empty($argv[1]))
{
	die("Syntax: ".$argv[0]." <ip[:port]>\n");
}
echo "Resolving...";
$server = \Phpcraft\Utils::resolve($argv[1]);
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
	echo \Phpcraft\Utils::chatToANSIText($info["description"])."\n\n";
}
else
{
	echo "This server has no description/MOTD.\n";
}
if(isset($info["version"]) && isset($info["version"]["protocol"]))
{
	if(\Phpcraft\Utils::isProtocolVersionSupported($info["version"]["protocol"]))
	{
		if(isset($info["version"]["name"]))
		{
			echo "This server is running a compatible ".$info["version"]["name"]." (".\Phpcraft\Utils::getMinecraftVersionFromProtocolVersion($info["version"]["protocol"]).") server.\n";
		}
		else
		{
			echo "This server is running a compatible ".\Phpcraft\Utils::getMinecraftVersionFromProtocolVersion($info["version"]["protocol"])." server.\n";
		}
	}
	else
	{
		if(isset($info["version"]["name"]))
		{
			echo "This server is running an incompatible ".$info["version"]["name"]." server.\n";
		}
		else
		{
			echo "This server is running an incompatible version.\n";
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
