<?php
// This plugin provides the ".reload" console command.
use Phpcraft\
{Plugin, PluginManager, ServerConsoleEvent};
PluginManager::registerPlugin("ServerReloadCommand", function(Plugin $plugin)
{
	$plugin->on(function(ServerConsoleEvent $event)
	{
		if(!$event->cancelled && $event->message == ".reload")
		{
			PluginManager::$loaded_plugins = [];
			echo "Unloaded all plugins.\nLoading plugins...\n";
			PluginManager::loadPlugins();
			echo count(PluginManager::$loaded_plugins)." plugins loaded.\n";
			$event->cancelled = true;
		}
	});
});
