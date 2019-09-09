<?php /** @noinspection PhpUndefinedNamespaceInspection PhpUndefinedClassInspection PhpComposerExtensionStubsInspection PhpUndefinedMethodInspection */
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
{ClientConnection, Command\CommandSender, Event\ServerJoinEvent, Event\ServerLeaveEvent, Event\ServerTickEvent, Item, Nbt\NbtCompound, Nbt\NbtInt, Nbt\NbtString, Packet\MapDataPacket, Packet\SetSlotPacket, Plugin, Slot};
$c = new Chromium();
if(!$c->isAvailable())
{
	echo "Downloading Chromium...";
	$c->download();
	echo " Done.\n";
}
$client_pages = [];
$i = $c->start(false);
//$i->logging = true;
$this->registerCommand("scale", function(CommandSender $con, float $scale)
{
	if(!$con instanceof ClientConnection)
	{
		$con->sendMessage("This command is only for players.");
		return;
	}
	global $client_pages;
	$client_pages[$con->username][1] = false;
	$client_pages[$con->username][0]->setDeviceMetrics(128 * (1 / $scale), 128 * (1 / $scale), $scale, function() use (&$con)
	{
		global $client_pages;
		$client_pages[$con->username][1] = true;
	});
});
$this->registerCommand("goto", function(CommandSender $con, string $url)
{
	if(!$con instanceof ClientConnection)
	{
		$con->sendMessage("This command is only for players.");
		return;
	}
	global $client_pages;
	$client_pages[$con->username][1] = false;
	$client_pages[$con->username][0]->once("Page.frameStoppedLoading", function() use (&$con)
	{
		global $client_pages;
		$client_pages[$con->username][1] = true;
	})
									->navigate($url);
});
$this->on(function(ServerJoinEvent $event) use (&$i)
{
	$i->newPage(function(Page $page) use (&$event)
	{
		$page->setDeviceMetrics(128 * (1 / 0.6), 128 * (1 / 0.6), 0.6, function() use (&$event, &$page)
		{
			$page->setDocumentContent("<body><h1>Welcome to Chromium on a Map!</h1><p>Use /scale 0.25 to continue.</p><p style='margin-top:50px;font-size:45px'>Well done, you're now seeing this page at 512x512 downscaled to 128x128.<br>Use /goto &lt;url&gt; to navigate somewhere on the interwebz.</p></body>", function() use (&$event, &$page)
			{
				global $client_pages;
				$client_pages[$event->client->username] = [
					$page,
					true
				];
				(new SetSlotPacket(0, Slot::HOTBAR_3, Item::get("filled_map")
														  ->slot(1, new NbtCompound("tag", [
															  new NbtCompound("display", [
																  new NbtString("Name", json_encode(["text" => "Chromium"]))
															  ]),
															  new NbtInt("map", 1338)
														  ]))))->send($event->client);
			});
		});
	});
});
$this->on(function(ServerLeaveEvent $event)
{
	global $client_pages;
	if(@array_key_exists($event->client->username, $client_pages))
	{
		$client_pages[$event->client->username][0]->close();
		unset($client_pages[$event->client->username]);
	}
});
$this->on(function(ServerTickEvent $event) use (&$i)
{
	if(!$i->isRunning())
	{
		echo "[Chromium] Chromium process is no longer running.\n";
		$this->unregister();
	}
	$i->handle();
	global $client_pages;
	foreach($event->server->getPlayers() as $client)
	{
		if(!@array_key_exists($client->username, $client_pages))
		{
			continue;
		}
		if(!$client_pages[$client->username][1])
		{
			continue;
		}
		$client_pages[$client->username][1] = false;
		$client_pages[$client->username][0]->captureScreenshot("png", [], function($data) use (&$client)
		{
			$packet = new MapDataPacket();
			$packet->mapId = 1338;
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
			$packet->send($client);
			global $client_pages;
			$client_pages[$client->username][1] = true;
		});
	}
	$i->handle();
});
$this->registerCommand("close_chromium", function(CommandSender $sender) use (&$i)
{
	if(!$sender->isOP())
	{
		$sender->sendMessage("This command is only for OPs.");
		return;
	}
	$i->close();
});
