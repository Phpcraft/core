<?php
// Loads a 128x128 image from map.png and displays it to clients as a map.
use Phpcraft\
{Event\Event, Event\ServerJoinEvent, Item, Nbt\NbtCompound, Nbt\NbtInt, Nbt\NbtString, Packet\MapDataPacket, Packet\SetSlotPacket, Phpcraft, Plugin, PluginManager, Slot};
$WorldImitatorActive = false;
PluginManager::registerPlugin("Map", function(Plugin $plugin)
{
	if(!extension_loaded("gd"))
	{
		echo "[Map] Please install the PHP gd extension.\n";
		$plugin->unregister();
		return;
	}
	$plugin->on(function(ServerJoinEvent $event)
	{
		if($event->cancelled)
		{
			return;
		}
		global $WorldImitatorActive;
		if($WorldImitatorActive)
		{
			return;
		}
		$con = $event->client;
		$packet = new SetSlotPacket();
		$packet->window = 0;
		$packet->slotId = Slot::HOTBAR_2;
		$packet->slot = new Slot(Item::get("filled_map"), 1, new NbtCompound("tag", [
			new NbtCompound("display", [
				new NbtString("Name", json_encode(Phpcraft::textToChat("§4§lMÄP")))
			]),
			new NbtInt("map", 1337),
		]));
		$packet->send($con);
		$packet = new MapDataPacket();
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
				array_push($packet->contents, MapDataPacket::getColorId([
					$r,
					$g,
					$b
				]));
			}
		}
		$packet->send($con);
	}, Event::PRIORITY_LOWEST);
});
