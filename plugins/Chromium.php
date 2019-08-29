<?php /** @noinspection PhpComposerExtensionStubsInspection */
/**
 * Allows you to browse the internet... on a map.
 *
 * @var Plugin $this
 */
if(!class_exists("pac\\Chromium"))
{
	echo "[Chromium] If you want to browse the internet on a map, run `composer require --dev hell-sh/pac:dev-master`. No guarantees that it will go well.\n";
	$this->unregister();
	return;
}
if(!extension_loaded("gd"))
{
	echo "[Chromium] Please install the PHP gd extension.\n";
	$this->unregister();
	return;
}
use pac\
{Chromium, Page};
use Phpcraft\
{ClientConnection, Command\CommandSender, Event\ServerTickEvent, Item, Nbt\NbtCompound, Nbt\NbtInt, Nbt\NbtString, Packet\MapDataPacket, Packet\SetSlotPacket, Plugin\Plugin, Slot};
$c = new Chromium();
if(!$c->isAvailable())
{
	echo "Downloading Chromium...";
	$c->download();
	echo " Done.\n";
}
$i = $c->start();
//$i->logging = true;
$this->registerCommand(["open", "navigate"], function(CommandSender $con, string $url, float $scale = 1) use (&$i)
{
	if(!$con instanceof ClientConnection)
	{
		$con->sendMessage("This command is only for players.");
		return;
	}
	$i->newPage(function(Page $page) use (&$con, &$url, &$scale)
	{
		$page->once("Page.frameStoppedLoading", function() use (&$con, &$url, &$scale, &$page)
		{
			$page->setDeviceMetrics(128 * (1 / $scale), 128 * (1 / $scale), $scale, function() use (&$con, &$url, &$page)
			{
				$page->captureScreenshot("png", [], function($data) use (&$con, &$url, &$page)
				{
					$page->close();
					$id = rand(0, 35536);
					(new SetSlotPacket(0, Slot::HOTBAR_2, Item::get("filled_map")
															  ->slot(1, new NbtCompound("tag", [
																  new NbtCompound("display", [
																	  new NbtString("Name", json_encode(["text" => $url]))
																  ]),
																  new NbtInt("map", $id)
															  ]))))->send($con);
					$packet = new MapDataPacket();
					$packet->mapId = $id;
					$packet->width = 128;
					$packet->height = 128;
					$img = imagecreatefromstring(base64_decode($data));
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
				});
			});
		})
			 ->navigate($url);
	});
});
$this->on(function(ServerTickEvent $event) use (&$i)
{
	if($i->isRunning())
	{
		$i->handle();
	}
	else
	{
		echo "Chromium process terminated, unregistering plugin.\n";
		$this->unregister();
	}
});
