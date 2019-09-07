<?php
/**
 * This plugin adds an annoying boss bar. Enjoy!
 *
 * @var Plugin $this
 */
use hellsh\UUID;
use Phpcraft\
{Event\Event, Event\ServerJoinEvent, Event\ServerTickEvent, Packet\BossBar\AddBossBarPacket, Packet\BossBar\UpdateBossBarHealthPacket, Packet\BossBar\UpdateBossBarTitlePacket, Plugin\Plugin};
global $bossbar_i;
$bossbar_i = 0;
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	$packet = new AddBossBarPacket(UUID::v5("BossBar.php"));
	$packet->title = ["text" => "Hello, world!"];
	$packet->send($event->client);
}, Event::PRIORITY_LOWEST);
$this->on(function(ServerTickEvent $event)
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
