<?php
use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("BossBar", function($plugin)
{
	$plugin->on("join", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		$con = $event->data["client"];
		$a = 0;
		$b = 0;
		foreach(explode(" ", "Never gonna give you up. Never gonna let you down. Never gonna run around and desert you. Never gonna make you cry. Never gonna say goodbye. Never gonna tell a lie and hurt you.") as $word)
		{
			$con->startPacket("boss_bar");
			$con->writeUuid(\Phpcraft\Uuid::v4());
			$con->writeVarInt(0);
			$con->writeChat(["text" => $word]);
			$con->writeFloat(100);
			$con->writeVarInt($a++);
			if($a > 6)
			{
				$a = 0;
			}
			$con->writeVarInt($b++);
			if($b > 4)
			{
				$b = 0;
			}
			$con->writeByte(0);
			$con->send();
		}
	}, \Phpcraft\Event::PRIORITY_LOWEST);
});
