<?php
/**
 * This plugin adds an annoying boss bar. Enjoy!
 *
 * @var Plugin $this
 */
use hellsh\UUID;
use Phpcraft\
{Event\Event, Event\ServerJoinEvent, Event\ServerTickEvent, Packet\BossBar\AddBossBarPacket, Packet\BossBar\UpdateBossBarHealthPacket, Packet\BossBar\UpdateBossBarTitlePacket, Plugin};
/** @noinspection PhpUndefinedFieldInspection */
$this->i = 0;
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
	foreach($event->server->getPlayers() as $con)
	{
		(new UpdateBossBarHealthPacket(UUID::v5("BossBar.php"), ($this->i - 91) / 91))->send($con);
		(new UpdateBossBarTitlePacket(UUID::v5("BossBar.php"), @str_repeat("|", $this->i).@str_repeat(".", (273 - $this->i))))->send($con);
	}
	if(++$this->i == 273)
	{
		$this->i = 0;
	}
});
