<?php
namespace Phpcraft;
use Exception;
use Phpcraft\Event\Event;
use SplObjectStorage;
abstract class PluginManager
{
	/**
	 * @var array<string,Plugin> $loaded_plugins
	 */
	public static $loaded_plugins = [];
	/**
	 * @var string $command_prefix
	 */
	public static $command_prefix = "/";
	/**
	 * @var SplObjectStorage $registered_commands
	 */
	public static $registered_commands;
	public static $plugin_folders = [
		"plugins",
		__DIR__."/../plugins"
	];

	/**
	 * Loads all plugins in all PluginManager::$plugin_folders
	 *
	 * @return void
	 */
	static function loadPlugins(): void
	{
		$loaded_folders = [];
		foreach(self::$plugin_folders as $folder)
		{
			$folder = realpath($folder);
			if(in_array($folder, $loaded_folders))
			{
				continue;
			}
			array_push($loaded_folders, $folder);
			foreach(scandir($folder) as $name)
			{
				if(substr($name, -4) == ".php" && is_file("$folder/$name"))
				{
					$name = substr($name, 0, -4);
				}
				else if(!is_dir("$folder/$name") || !is_file("$folder/$name/$name.php"))
				{
					continue;
				}
				if(array_key_exists($name, self::$loaded_plugins))
				{
					echo "A plugin called $name is already loaded, not loading $name from $folder\n";
				}
				try
				{
					self::$loaded_plugins[$name] = new Plugin($folder, $name);
				}
				catch(Exception $e)
				{
					echo "Unhandled exception in plugin \"$name\": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
				}
			}
		}
	}

	/**
	 * @return void
	 */
	static function unloadAllPlugins(): void
	{
		PluginManager::$loaded_plugins = [];
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

PluginManager::$registered_commands = new SplObjectStorage();
