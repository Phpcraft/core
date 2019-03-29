<?php
namespace Phpcraft;
abstract class PluginManager
{
	private static $load_state;
	/**
	 * A Plugin array of plugins currently loaded.
	 * @var array $loaded_plugins
	 */
	public static $loaded_plugins = [];

	/**
	 * Loads all plugins in a folder.
	 * @param string $plugins_folder The path to the folder in which plugins are contained.
	 */
	public static function loadPlugins($plugins_folder = "plugins")
	{
		foreach(scandir($plugins_folder) as $file)
		{
			if(substr($file, -4) == ".php" && is_file($file))
			{
				PluginManager::$load_state = true;
				include $plugins_folder."/".$file;
				if(!PluginManager::$load_state)
				{
					echo "{$file} did not register with PluginManager::registerPlugin\n";
				}
			}
		}
	}

	/**
	 * The function called by plugins when they would like to be registered.
	 * @param string $name This has to be identical to the name of file exluding the extension.
	 * @param callable $callback The callback function called with a Plugin as parameter.
	 * @return void
	 */
	public static function registerPlugin($name, $callback)
	{
		if(PluginManager::$load_state)
		{
			$plugin = new Plugin($name);
			try
			{
				$callback($plugin);
			}
			catch(\Exception $e)
			{
				echo "Unhandled exception in plugin \"{$name}\": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
			}
			array_push(PluginManager::$loaded_plugins, $plugin);
			PluginManager::$load_state = false;
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
	public static function fire(Event $event)
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
		catch(\Exception $e)
		{
			echo "Unhandled exception in plugin: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
		}
		return $event->cancelled;
	}
}
