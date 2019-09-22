<?php
/**
 * Provides the management commands "/reload" and "/stop".
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
		if(is_file("config/server.json"))
		{
			$sender->sendAndPrintMessage("Reloading server config...");
			global $config, $server;
			$config = json_decode(file_get_contents("config/server.json"), true);
			$server->compression_threshold = $config["compression_threshold"];
			$server->setGroups($config["groups"]);
			$sender->sendAndPrintMessage("Done. ".count($sender->getServer()->groups)." groups loaded.");
		}
		else
		{
			$sender->sendAndPrintMessage("server.json was deleted. keeping current config. restart the server to apply defaults.");
		}
	}
}, "use /reload");
$this->registerCommand("stop", function(CommandSender &$sender)
{
	if($sender->hasServer())
	{
		$sender->sendAndPrintMessage("Stopping server...");
		$sender->getServer()
			   ->close(["text" => "/stop has been issued by ".$sender->getName()]);
	}
	else
	{
		$sender->sendMessage([
			"text" => "This command only works for servers.",
			"color" => "red"
		]);
	}
}, "use /stop");
