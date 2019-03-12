<?php
use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("BossBar", function($plugin)
{
	global $bossbar_uuid, $bossbar_i;
	$bossbar_uuid = \Phpcraft\Uuid::v5("BossBar.php");
	$bossbar_i = 0;
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		global $bossbar_uuid;
		$packet = new \Phpcraft\AddBossBarPacket($bossbar_uuid);
		$packet->send($event->data["client"]);
	}, \Phpcraft\Event::PRIORITY_LOWEST);
	$plugin->on("tick", function($event)
	{
		global $bossbar_uuid, $bossbar_i;
		foreach($event->data["server"]->clients as $con)
		{
			(new \Phpcraft\UpdateBossBarHealthPacket($bossbar_uuid, ($bossbar_i - 91) / 91))->send($con);
			(new \Phpcraft\UpdateBossBarTitlePacket($bossbar_uuid, @str_repeat("|", $bossbar_i).@str_repeat(".", (273 - $bossbar_i))))->send($con);
		}
		if(++$bossbar_i == 273)
		{
			$bossbar_i = 0;
		}
	});
});
