<?php
namespace Phpcraft;
use Exception;
use Phpcraft\Event\Event;
use SplObjectStorage;
abstract class PluginManager
{
	/**
	 * @var SplObjectStorage $loaded_plugins
	 */
	public static $loaded_plugins;
	/**
	 * @var string $command_prefix
	 */
	public static $command_prefix = "/";
	/**
	 * @var SplObjectStorage $registered_commands
	 */
	public static $registered_commands;

	/**
	 * Loads all plugins in a folder.
	 *
	 * @param string $plugins_folder The path to the folder in which plugins are contained.
	 * @return void
	 */
	static function loadPlugins(string $plugins_folder = "plugins"): void
	{
		foreach(scandir($plugins_folder) as $name)
		{
			if(substr($name, -4) == ".php" && is_file("$plugins_folder/$name"))
			{
				$name = substr($name, 0, -4);
			}
			else if(!is_dir("$plugins_folder/$name") || !is_file("$plugins_folder/$name/$name.php"))
			{
				continue;
			}
			try
			{
				$plugin = new Plugin($plugins_folder, $name);
				self::$loaded_plugins->attach($plugin);
			}
			catch(Exception $e)
			{
				echo "Unhandled exception in plugin \"$name\": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
			}
		}
	}

	/**
	 * @return void
	 */
	static function unloadAllPlugins(): void
	{
		PluginManager::$loaded_plugins = new SplObjectStorage();
		PluginManager::$registered_commands = new SplObjectStorage();
	}

	/**
	 * Fires an Event to all loaded plugins.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	static function fire(Event $event): bool
	{
		$type = get_class($event);
		$handlers = [];
		foreach(PluginManager::$loaded_plugins as $plugin)
		{
			if(isset($plugin->event_handlers[$type]))
			{
				array_push($handlers, $plugin->event_handlers[$type]);
			}
		}
		usort($handlers, function(array $a, array $b)
		{
			return $b["priority"] - $a["priority"];
		});
		try
		{
			foreach($handlers as $handler)
			{
				$handler["function"]($event);
			}
		}
		catch(Exception $e)
		{
			echo "Unhandled exception in plugin: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
		}
		return $event->cancelled;
	}
}

PluginManager::unloadAllPlugins();
