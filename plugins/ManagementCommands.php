<?php
/**
 * Provides the management commands "/reload" and "/stop".
 *
 * @var Plugin $this
 */
use Phpcraft\
{Command\ServerCommandSender, Plugin, PluginManager};
if(PluginManager::$command_prefix != "/")
{
	$this->unregister();
	return;
}
$this->registerCommand("reload", function(ServerCommandSender &$sender)
{
	PluginManager::unloadAllPlugins();
	$sender->sendAdminBroadcast("Unloaded all plugins.", "use /reload");
	$sender->sendAdminBroadcast("Loading plugins...", "use /reload");
	PluginManager::loadPlugins();
	$sender->sendAdminBroadcast(count(PluginManager::$loaded_plugins)." plugins loaded.", "use /reload");
	$sender->sendAdminBroadcast("Reloading server config...", "use /reload");
	reloadConfiguration();
	$sender->sendAdminBroadcast("Done. ".count($sender->getServer()->groups)." groups loaded.", "use /reload");
}, "use /reload");
$this->registerCommand("stop", function(ServerCommandSender &$sender)
{
	$sender->sendAdminBroadcast("Stopping server...");
	$sender->getServer()
		   ->close(["text" => "/stop has been issued by ".$sender->getName()]);
}, "use /stop");
$this->registerCommand("close", function(ServerCommandSender &$sender)
{
	$sender->sendAdminBroadcast("Closing server...");
	$sender->getServer()
		   ->softClose();
	$sender->sendAdminBroadcast("Done. The server will shutdown once empty. Use /reload to reopen listening sockets.");
}, "use /stop");
