<?php
use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
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
			$con->startPacket("set_slot");
			$con->writeByte(0);
			$con->writeShort(36);
			$con->writeBoolean(true);
			$con->writeVarInt(613);
			$con->writeByte(1);
			(new \Phpcraft\NbtCompound("tag", [
				new \Phpcraft\NbtCompound("display", [
					new \Phpcraft\NbtString("Name", json_encode(["text" => "MÄP", "color" => "dark_red", "bold" => true]))
				]),
				new \Phpcraft\NbtInt("map", 69420),
			]))->send($con);
			$con->send();
			$con->writeVarInt(0x26); // Map Data
			$con->writeVarInt(69420);
			$con->writeByte(0);
			$con->writeBoolean(true);
			$con->writeVarInt(2);
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
			$con->writeByte(128);
			$con->writeByte(128);
			$con->writeByte(0);
			$con->writeByte(0);
			$con->writeVarInt(128 * 128);
			$color = 4;
			for($i = 0; $i < 128 * 128; $i++)
			{
				$con->writeByte($color);
				$color++;
				if($color > 132)
				{
					$color = 4;
				}
			}
			$con->send();
		}
	});
});
