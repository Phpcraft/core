<?php
/**
 * This plugin provides the "/reload" command to the server console.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Command\CommandSender, Plugin\Plugin, Plugin\PluginManager};
$this->registerCommand("reload", function(CommandSender &$sender)
{
	if(!$sender->isOP())
	{
		$sender->sendMessage([
			"text" => "You need to be OP in order to use this command.",
			"color" => "red"
		]);
		return;
	}
	PluginManager::unloadAllPlugins();
	echo "Unloaded all plugins.\nLoading plugins...\n";
	PluginManager::loadPlugins();
	echo count(PluginManager::$loaded_plugins)." plugins loaded.\n";
});
