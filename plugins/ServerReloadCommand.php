<?php
/**
 * This plugin provides the "/reload" command to the server console.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Command\CommandSender, Plugin, PluginManager};
if(PluginManager::$command_prefix == "/proxy:")
{
	$this->unregister();
	return;
}
$this->registerCommand("reload", function(CommandSender &$sender)
{
	PluginManager::unloadAllPlugins();
	$sender->sendAndPrintMessage("Unloaded all plugins.");
	$sender->sendAndPrintMessage("Loading plugins...");
	PluginManager::loadPlugins();
	$sender->sendAndPrintMessage(count(PluginManager::$loaded_plugins)." plugins loaded.");
	if($sender->hasServer())
	{
		if(is_file("config/groups.json"))
		{
			$sender->sendAndPrintMessage("Reloading groups...");
			$sender->getServer()
				   ->setGroups(json_decode(file_get_contents("config/groups.json"), true));
			$sender->sendAndPrintMessage(count($sender->getServer()->groups)." groups loaded.");
		}
		else
		{
			$sender->sendAndPrintMessage("groups.json was deleted. keeping current groups. restart the server to apply defaults.");
		}
	}
}, "use /reload");
