<?php
use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("BossBar", function($plugin)
{
	global $bossbar_i;
	$bossbar_i = 0;
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		$packet = new \Phpcraft\AddBossBarPacket(\Phpcraft\UUID::v5("BossBar.php"));
		$packet->title = ["text" => "Hello, world!"];
		$packet->send($event->data["client"]);
	}, \Phpcraft\Event::PRIORITY_LOWEST);
	$plugin->on("tick", function($event)
	{
		global $bossbar_i;
		foreach($event->data["server"]->clients as $con)
		{
			if($con->state != 3)
			{
				continue;
			}
			(new \Phpcraft\UpdateBossBarHealthPacket(\Phpcraft\UUID::v5("BossBar.php"), ($bossbar_i - 91) / 91))->send($con);
			(new \Phpcraft\UpdateBossBarTitlePacket(\Phpcraft\UUID::v5("BossBar.php"), @str_repeat("|", $bossbar_i).@str_repeat(".", (273 - $bossbar_i))))->send($con);
		}
		if(++$bossbar_i == 273)
		{
			$bossbar_i = 0;
		}
	});
});
