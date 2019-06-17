<?php
// This plugin adds an annoying boss bar. Enjoy!
use Phpcraft\
{AddBossBarPacket, Event\Event, Event\ServerJoinEvent, Event\ServerTickEvent, Plugin, PluginManager, UpdateBossBarHealthPacket, UpdateBossBarTitlePacket, UUID};
PluginManager::registerPlugin("BossBar", function(Plugin $plugin)
{
	global $bossbar_i;
	$bossbar_i = 0;
	$plugin->on(function(ServerJoinEvent $event)
	{
		if($event->cancelled)
		{
			return;
		}
		$packet = new AddBossBarPacket(UUID::v5("BossBar.php"));
		$packet->title = ["text" => "Hello, world!"];
		$packet->send($event->client);
	}, Event::PRIORITY_LOWEST);
	$plugin->on(function(ServerTickEvent $event)
	{
		global $bossbar_i;
		foreach($event->server->getPlayers() as $con)
		{
			(new UpdateBossBarHealthPacket(UUID::v5("BossBar.php"), ($bossbar_i - 91) / 91))->send($con);
			(new UpdateBossBarTitlePacket(UUID::v5("BossBar.php"), @str_repeat("|", $bossbar_i).@str_repeat(".", (273 - $bossbar_i))))->send($con);
		}
		if(++$bossbar_i == 273)
		{
			$bossbar_i = 0;
		}
	});
});
