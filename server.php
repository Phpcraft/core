<?php
echo "Phpcraft PHP Minecraft Server\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php server.php`.\n");
}
require "vendor/autoload.php";
use Phpcraft\
{Command\Command, Event\ServerConsoleEvent, Event\ServerTickEvent, IntegratedServer, PluginManager};
$server = IntegratedServer::cliStart("Phpcraft Server", [
	"groups" => [
		"default" => [
			"allow" => [
				"use /me",
				"use /gamemode",
				"use /metadata",
				"change the world"
			]
		],
		"user" => [
			"inherit" => "default",
			"allow" => [
				"use /abilities",
				"use chromium"
			]
		],
		"admin" => [
			"allow" => "everything"
		]
	]
]);
echo "Loading plugins...\n";
PluginManager::loadPlugins();
echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
$server->ui->render();
$server->persist_configs = true;
$server->config_reloaded_function = function() use (&$server)
{
	$server->setGroups($server->config["groups"]);
};
($server->config_reloaded_function)();
$next_tick = microtime(true) + 0.05;
do
{
	$start = microtime(true);
	$server->accept();
	$server->handle();
	while($msg = $server->ui->render(true))
	{
		if(Command::handleMessage($server, $msg) || PluginManager::fire(new ServerConsoleEvent($server, $msg)))
		{
			continue;
		}
		$msg = [
			"translate" => "chat.type.announcement",
			"with" => [
				[
					"text" => "Server"
				],
				[
					"text" => $msg
				]
			]
		];
		$server->broadcast($msg);
	}
	if($next_tick < microtime(true))
	{
		$next_tick += 0.05;
		PluginManager::fire(new ServerTickEvent($server, $next_tick < microtime(true)));
	}
	if(($remaining = (0.001 - (microtime(true) - $start))) > 0)
	{
		time_nanosleep(0, $remaining * 1000000000);
	}
}
while($server->isOpen());
$ui->add("Server is not listening on any ports and has no clients, so it's shutting down.");
$ui->render();
