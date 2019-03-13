<?php
namespace Phpcraft;
class PluginManager
{
	/**
	 * The name of the platform plugins will run on, e.g. `phpcraft:client` or `phpcraft:server`.
	 * @var string $platform
	 */
	public static $platform;
	private static $loadee_name;
	/**
	 * A Plugin array of plugins currently loaded.
	 * @var array $loaded_plugins
	 */
	public static $loaded_plugins = [];

	/**
	 * Reads the autoload.txt of the plugin folder and loads all plugins.
	 * @param string $plugins_folder The path to the folder in which plugins are contained.
	 */
	static function autoloadPlugins($plugins_folder = "plugins")
	{
		foreach(file($plugins_folder."/autoload.txt") as $line)
		{
			if($line = trim($line))
			{
				if(substr($line, 0, 1) == "#")
				{
					continue;
				}
				if(file_exists($plugins_folder."/".$line.".php"))
				{
					PluginManager::$loadee_name = $line;
					include $plugins_folder."/".$line.".php";
					PluginManager::$loadee_name = null;
				}
			}
		}
	}

	/**
	 * The function called by plugins when they would like to be registered.
	 * @param $name This has to be identical to the name of file exluding the extension.
	 * @param $callback The callback function called with a Plugin as parameter.
	 * @return void
	 */
	static function registerPlugin($name, $callback)
	{
		if(PluginManager::$loadee_name && PluginManager::$loadee_name == $name)
		{
			$plugin = new \Phpcraft\Plugin($name);
			($callback)($plugin);
			array_push(PluginManager::$loaded_plugins, $plugin);
		}
		else
		{
			echo "Plugin \"{$name}\" tried to be registered despite not having been asked.\n";
		}
	}

	/**
	 * Fires an Event to all loaded plugins.
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	static function fire($event)
	{
		$handlers = [];
		foreach(PluginManager::$loaded_plugins as $plugin)
		{
			if(isset($plugin->event_handlers[$event->name]))
			{
				array_push($handlers, $plugin->event_handlers[$event->name]);
			}
			else if(isset($plugin->event_handlers["."]))
			{
				array_push($handlers, $plugin->event_handlers["."]);
			}
			if(isset($plugin->event_handlers["*"]))
			{
				array_push($handlers, $plugin->event_handlers["*"]);
			}
		}
		usort($handlers, function($a, $b)
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
		return $event->isCancelled();
	}
}
