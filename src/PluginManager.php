<?php
namespace Phpcraft;
use SplObjectStorage;
use hotswapp\PluginManager as HotswappPluginManager;
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
	 * @param string $folder
	 * @param string $name
	 * @return Plugin
	 */
	protected static function constructPlugin(string $folder, string $name)
	{
		return new Plugin($folder, $name);
	}

	/**
	 * @return void
	 */
	static function unloadAllPlugins(): void
	{
		HotswappPluginManager::unloadAllPlugins();
		PluginManager::$registered_commands = new SplObjectStorage();
	}
}

array_push(HotswappPluginManager::$plugin_folders, __DIR__."/../plugins");
PluginManager::$registered_commands = new SplObjectStorage();
