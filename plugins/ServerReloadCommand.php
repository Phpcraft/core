<?php
// The server currently doesn't have commands, so this plugin will at least provide a ".reload" command.

use \Phpcraft\PluginManager;
if(!in_array(PluginManager::$platform, ["phpcraft:server"]))
{
	return;
}
PluginManager::registerPlugin("ServerReloadCommand", function($plugin)
{
	$plugin->on("console_message", function($event)
	{
		if($event->isCancelled())
		{
			return;
		}
		if($event->data["message"] == ".reload")
		{
			PluginManager::$loaded_plugins = [];
			echo "Reloading plugins... ";
			PluginManager::autoloadPlugins();
			echo count(\Phpcraft\PluginManager::$loaded_plugins)." plugins are loaded now.\n";
			$event->cancel();
		}
	});
});
