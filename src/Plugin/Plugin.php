<?php
namespace Phpcraft\Plugin;
use DomainException;
use RuntimeException;
use InvalidArgumentException;
use Phpcraft\Command\Command;
use Phpcraft\Event\Event;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
class Plugin
{
	/**
	 * The name of the plugin.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * The namespace of the plugin.
	 *
	 * @var string $namespace
	 */
	public $namespace;
	/**
	 * An associative array of associative arrays with a 'function' and 'priority'.
	 *
	 * @var array $event_handlers
	 */
	public $event_handlers = [];

	/**
	 * The constructor.
	 * Don't call this unless you know what you're doing.
	 *
	 * @param string $name The name of the plugin.
	 * @param string $folder The path of the folder the plugin was loaded from.
	 * @see PluginManager::loadPlugins()
	 */
	function __construct(string $folder, string $name)
	{
		$this->name = $name;
		$this->namespace = strtolower($name);
		/** @noinspection PhpIncludeInspection */
		require "$folder/$name.php";
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	function fire(Event $event): bool
	{
		$type = get_class($event);
		if(isset($this->event_handlers[$type]))
		{
			($this->event_handlers[$type])($event);
		}
		return $event->cancelled;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 *
	 * @param callable $callable The function. The first parameter should explicitly declare its type to be a decendant of Event.
	 * @param integer $priority The priority of the event handler. The higher the priority, the earlier it will be executed. Use a high value if you plan to cancel the event.
	 * @return Plugin $this
	 * @throws InvalidArgumentException
	 */
	protected function on(callable $callable, int $priority = Event::PRIORITY_NORMAL): Plugin
	{
		try
		{
			$params = (new ReflectionFunction($callable))->getParameters();
			if(count($params) != 1)
			{
				throw new InvalidArgumentException("Callable needs to have exactly one parameter.");
			}
			$param = $params[0];
			if(!$param->hasType())
			{
				throw new InvalidArgumentException("Callable's parameter needs to explicitly declare parameter type.");
			}
			$type = $param->getType();
			/** @noinspection PhpDeprecationInspection */
			$type = $type instanceof ReflectionNamedType ? $type->getName() : $type->__toString();
			$class = new ReflectionClass($type);
			if(!$class->isSubclassOf("Phpcraft\\Event\\Event"))
			{
				throw new InvalidArgumentException("Callable's parameter type needs to be a decendant of \\Phpcraft\\Event.");
			}
			$this->event_handlers[$type] = [
				"priority" => $priority,
				"function" => $callable
			];
		}
		catch(ReflectionException $e)
		{
			throw new RuntimeException("Unexpected exception: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
		}
		return $this;
	}

	/**
	 * Registers a command.
	 *
	 * @param $names string|string[] One or more names without the / prefix. So, if you want a "/gamemode" comand, you provide "gamemode", and if you want a "//wand" command, you provide "/wand".
	 * @param callable $function The function called when the command is executed with the first argument being a CommandSender. Further arguments determine the command's arguments, e.g. <code>-&gt;registerCommand("gamemode", function(CommandSender &$sender, int $gamemode){...})</code> would result in the command <code>/gamemode &lt;gamemode&gt;</code> where the gamemode argument only allows integers.
	 * @return Plugin $this
	 */
	protected function registerCommand($names, callable $function): Plugin
	{
		if(is_string($names))
		{
			$names = [$names];
		}
		$names_ = [];
		foreach($names as $name)
		{
			foreach(PluginManager::$registered_commands as $command)
			{
				if(in_array($this->namespace.":".$name, $command->names))
				{
					throw new DomainException("/{$name} is already registered");
				}
			}
			array_push($names_, $this->namespace.":".$name);
			foreach(PluginManager::$registered_commands as $command)
			{
				if(strpos($name, ":") !== false)
				{
					throw new DomainException("Invalid command name: /{$name}");
				}
				if(in_array($name, $command->names))
				{
					trigger_error("/{$name} was already registered by {$command->plugin->name}; it will still be accessible using /{$this->namespace}:{$name}, but maybe sort out your plugins, will ya?");
					continue 2;
				}
			}
			array_push($names_, $name);
		}
		assert(count($names_) > 0);
		array_push(PluginManager::$registered_commands, new Command($this, $names_, $function));
		return $this;
	}

	/**
	 * Unregisters the plugin.
	 * This is useful, for example, if your plugin had a fatal error.
	 */
	protected function unregister()
	{
		unset(PluginManager::$loaded_plugins[$this->name]);
		echo "Plugin \"".$this->name."\" has unregistered.\n";
	}
}
