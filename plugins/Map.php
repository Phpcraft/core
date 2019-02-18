<?php
use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
if(!extension_loaded("gd"))
{
	echo "[Map] Not loading because php-gd is not loaded.\n";
	return;
}
PluginManager::registerPlugin("Map", function($plugin)
{
	$plugin->on("joined", function($event)
	{
		$con = $event->data["client"];
		if($con->protocol_version >= 393 && $con->protocol_version <= 404)
		{
			$con->write_buffer = "";
			$packet = new \Phpcraft\SetSlotPacket();
			$packet->window = 0;
			$packet->slotId = \Phpcraft\Slot::ID_HOTBAR_1;
			$packet->slot = new \Phpcraft\Slot(
				\Phpcraft\Item::get("filled_map"),
				1,
				new \Phpcraft\NbtCompound("tag", [
					new \Phpcraft\NbtCompound("display", [
						new \Phpcraft\NbtString("Name", json_encode(["text" => "MÄP", "color" => "dark_red", "bold" => true]))
					]),
					new \Phpcraft\NbtInt("map", 69420),
				])
			);
			$packet->send($con);
			$con->writeVarInt(0x26); // Map Data
			$con->writeVarInt(69420);
			$con->writeByte(0);
			$con->writeBoolean(true);
			$con->writeVarInt(0);
			/*
			$con->writeVarInt(7);
			$con->writeByte(0);
			$con->writeByte(-3);
			$con->writeByte(0);
			$con->writeBoolean(true);
			$con->writeString(json_encode(["text" => "In Soviet Russia,"]));
			$con->writeVarInt(7);
			$con->writeByte(0);
			$con->writeByte(3);
			$con->writeByte(0);
			$con->writeBoolean(true);
			$con->writeString(json_encode(\Phpcraft\Phpcraft::textToChat("§4§lMÄP§r looks at you!")));
			*/
			$con->writeByte(128);
			$con->writeByte(128);
			$con->writeByte(0);
			$con->writeByte(0);
			$con->writeVarInt(16384);
			$img = imagecreatefrompng("map.png");
			for($y = 0; $y < 128; $y++)
			{
				for($x = 0; $x < 128; $x++)
				{
					$rgb = imagecolorat($img, $x, $y);
					$r = ($rgb >> 16) & 0xFF;
					$g = ($rgb >> 8) & 0xFF;
					$b = $rgb & 0xFF;
					$con->writeByte(\Phpcraft\MapDataPacket::getColorId([$r, $g, $b]));
				}
			}
			$con->send();
		}
	});
});
