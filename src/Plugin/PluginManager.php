<?php
namespace Phpcraft\Plugin;
use Exception;
use Phpcraft\
{Command\Command, Event\Event};
abstract class PluginManager
{
	/**
	 * @var $loaded_plugins Plugin[]
	 */
	public static $loaded_plugins = [];
	/**
	 * @var $registered_commands Command[]
	 */
	public static $registered_commands = [];

	/**
	 * Loads all plugins in a folder.
	 *
	 * @param string $plugins_folder The path to the folder in which plugins are contained.
	 */
	static function loadPlugins(string $plugins_folder = "plugins")
	{
		foreach(scandir($plugins_folder) as $file)
		{
			if(substr($file, -4) == ".php" && is_file($plugins_folder."/".$file))
			{
				$name = substr($file, 0, -4);
				try
				{
					$plugin = new Plugin($plugins_folder, $name);
					array_push(self::$loaded_plugins, $plugin);
				}
				catch(Exception $e)
				{
					echo "Unhandled exception in plugin \"$name\": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
				}
			}
		}
	}

	static function unloadAllPlugins()
	{
		PluginManager::$loaded_plugins = [];
		PluginManager::$registered_commands = [];
	}

	/**
	 * Fires an Event to all loaded plugins.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	static function fire(Event $event)
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
