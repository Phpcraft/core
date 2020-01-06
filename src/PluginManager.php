<?php
namespace Phpcraft;
use hotswapp\PluginManager as HotswappPluginManager;
use SplObjectStorage;
abstract class PluginManager extends HotswappPluginManager
{
	/**
	 * @var string $command_prefix
	 */
	public static $command_prefix = "/";
	/**
	 * @var SplObjectStorage $registered_commands
	 */
	public static $registered_commands;

	/**
	 * @return void
	 */
	static function unloadAllPlugins(): void
	{
		HotswappPluginManager::unloadAllPlugins();
		PluginManager::$registered_commands = new SplObjectStorage();
	}

	/**
	 * @param string $folder
	 * @param string $name
	 * @return Plugin
	 */
	protected static function constructPlugin(string $folder, string $name)
	{
		return new Plugin($folder, $name);
	}
}

array_push(HotswappPluginManager::$plugin_folders, __DIR__."/../plugins");
PluginManager::$registered_commands = new SplObjectStorage();
