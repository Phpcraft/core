<?php
/**
 * This plugin provides the ".reload" console command.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Event\ServerConsoleEvent, Plugin, PluginManager};
$this->on(function(ServerConsoleEvent $event)
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
