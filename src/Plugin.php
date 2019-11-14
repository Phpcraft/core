<?php /** @noinspection PhpIncludeInspection */
namespace Phpcraft;
use Closure;
use DomainException;
use InvalidArgumentException;
use Phpcraft\Command\Command;
use Phpcraft\Event\Event;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use RuntimeException;
class Plugin
{
	/**
	 * The name of the plugin.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * @var array<array{function:Closure,priority:int}> $event_handlers
	 */
	public $event_handlers = [];
	private $unregistered = false;

	/**
	 * Don't call this unless you know what you're doing.
	 *
	 * @param string $folder The path of the folder the plugin was loaded from.
	 * @param string $name The name of the plugin.
	 * @see PluginManager::loadPlugins()
	 */
	function __construct(string $folder, string $name)
	{
		$this->name = $name;
		if(is_file("$folder/$name.php"))
		{
			require "$folder/$name.php";
		}
		else if(is_file("$folder/$name/$name.php"))
		{
			require "$folder/$name/$name.php";
		}
		else
		{
			throw new RuntimeException("Couldn't find out how to load plugin \"$name\"");
		}
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 *
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	function fire(Event $event): bool
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::fire() after Plugin::unregister()");
		}
		$type = get_class($event);
		if(isset($this->event_handlers[$type]))
		{
			($this->event_handlers[$type]["function"])($event);
		}
		return $event->cancelled;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 *
	 * @param Closure $function The function. The first parameter should explicitly declare its type to be a decendant of Event.
	 * @param int $priority The priority of the event handler. The higher the priority, the earlier it will be executed. Use a high value if you plan to cancel the event.
	 * @return Plugin $this
	 */
	protected function on(Closure $function, int $priority = Event::PRIORITY_NORMAL): Plugin
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::on() after Plugin::unregister()");
		}
		try
		{
			$params = (new ReflectionFunction($function))->getParameters();
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
				"function" => $function,
				"priority" => $priority
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
	 * @param string|array<string> $names One or more names without the / prefix. So, if you want a "/gamemode" comand, you provide "gamemode", and if you want a "//wand" command, you provide "/wand".
	 * @param callable $function The function called when the command is executed. The first argument will be the CommandSender but if type-hinted to be ClientConnection or Server it will be exclusive to players or the server, respectively. Further arguments determine the command's arguments, e.g. <code>-&gt;registerCommand("gamemode", function(ClientConnection &$con, int $gamemode){...})</code> would result in the player-exclusive command <code>/gamemode &lt;gamemode&gt;</code> where the gamemode argument only allows integers.
	 * @param string|null $required_permission The permission required to use this command or null if not applicable.
	 * @return Plugin $this
	 */
	protected function registerCommand($names, callable $function, ?string $required_permission = null): Plugin
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::on() after Plugin::unregister()");
		}
		if(is_string($names))
		{
			$names = [$names];
		}
		foreach($names as $name)
		{
			foreach(PluginManager::$registered_commands as $command)
			{
				if(in_array($name, $command->names))
				{
					throw new DomainException(PluginManager::$command_prefix.$name." is already registered");
				}
			}
		}
		PluginManager::$registered_commands->attach(new Command($this, $names, $required_permission, $function));
		return $this;
	}

	/**
	 * Unregisters the plugin, including its event handlers and its commands.
	 * Make sure your plugin has no statements other than `return;` after this.
	 *
	 * @return void
	 */
	protected function unregister(): void
	{
		unset(PluginManager::$loaded_plugins[$this->name]);
		foreach(PluginManager::$registered_commands as $command)
		{
			if($command->plugin == $this)
			{
				PluginManager::$registered_commands->detach($command);
			}
		}
		$this->event_handlers = [];
		$this->unregistered = true;
	}
}
