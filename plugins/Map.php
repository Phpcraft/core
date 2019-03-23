<?php
// Loads a 128x128 image from map.png and displays it to clients as a map.

use Phpcraft\
{Event, Plugin, PluginManager};

if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
if(!extension_loaded("gd"))
{
	echo "[Map] Not loading because php-gd is not loaded.\n";
	return;
}
PluginManager::registerPlugin("Map", function(Plugin $plugin)
{
	$plugin->on("join", function(Event $event)
	{
		if($event->isCancelled())
		{
			return;
		}
		$con = $event->data["client"];
		$packet = new \Phpcraft\SetSlotPacket();
		$packet->window = 0;
		$packet->slotId = \Phpcraft\Slot::ID_HOTBAR_1;
		$packet->slot = new \Phpcraft\Slot(
			\Phpcraft\Item::get("filled_map"),
			1,
			new \Phpcraft\NbtCompound("tag", [
				new \Phpcraft\NbtCompound("display", [
					new \Phpcraft\NbtString("Name", json_encode(\Phpcraft\Phpcraft::textToChat("§4§lMÄP")))
				]),
				new \Phpcraft\NbtInt("map", 1337),
			])
		);
		$packet->send($con);
		$packet = new \Phpcraft\MapDataPacket();
		$packet->mapId = 1337;
		$packet->width = 128;
		$packet->height = 128;
		$img = imagecreatefrompng("map.png");
		for($y = 0; $y < 128; $y++)
		{
			for($x = 0; $x < 128; $x++)
			{
				$rgb = imagecolorat($img, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				array_push($packet->contents, Phpcraft\MapDataPacket::getColorId([$r, $g, $b]));
			}
		}
		$packet->send($con);
	}, \Phpcraft\Event::PRIORITY_LOWEST);
});
