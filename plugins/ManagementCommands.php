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
	if(!$sender->hasServer())
	{
		$sender->sendMessage([
			"text" => "This command only works for servers.",
			"color" => "red"
		]);
		return;
	}
	PluginManager::unloadAllPlugins();
	$sender->sendAdminBroadcast("Unloaded all plugins.", "use /reload");
	$sender->sendAdminBroadcast("Loading plugins...", "use /reload");
	PluginManager::loadPlugins();
	$sender->sendAdminBroadcast(count(PluginManager::$loaded_plugins)." plugins loaded.", "use /reload");
	if($sender->hasServer())
	{
		$sender->sendAdminBroadcast("Reloading server config...", "use /reload");
		reloadConfiguration();
		$sender->sendAdminBroadcast("Done. ".count($sender->getServer()->groups)." groups loaded.", "use /reload");
	}
}, "use /reload");
$this->registerCommand("stop", function(CommandSender &$sender)
{
	if(!$sender->hasServer())
	{
		$sender->sendMessage([
			"text" => "This command only works for servers.",
			"color" => "red"
		]);
		return;
	}
	$sender->sendAdminBroadcast("Stopping server...");
	$sender->getServer()
		   ->close(["text" => "/stop has been issued by ".$sender->getName()]);
}, "use /stop");
$this->registerCommand("close", function(CommandSender &$sender)
{
	if(!$sender->hasServer())
	{
		$sender->sendMessage([
			"text" => "This command only works for servers.",
			"color" => "red"
		]);
		return;
	}
	$sender->sendAdminBroadcast("Closing server...");
	$sender->getServer()->softClose();
	$sender->sendAdminBroadcast("Done. The server will shutdown once empty. Use /reload to reopen listening sockets.");
}, "use /stop");
