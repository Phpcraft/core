<?php
namespace Phpcraft;
use InvalidArgumentException;
use ReflectionException;
class Plugin
{
	/**
	 * Tha name of the plugin.
	 * @var string $name
	 */
	public $name;
	/**
	 * An associative array of associative arrays with a 'function' and 'priority'.
	 * @var array $event_handlers
	 */
	public $event_handlers = [];

	/**
	 * @param string $name The name of the plugin.
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 * @param callable $callable The function. The first parameter should explicitly declare its type to be a decendant of Event.
	 * @param integer $priority The priority of the event handler. The higher the priority, the earlier it will be executed. Use a high value if you plan to cancel the event.
	 * @return Plugin $this
	 * @throws InvalidArgumentException
	 * @throws ReflectionException
	 */
	public function on(callable $callable, int $priority = Event::PRIORITY_NORMAL)
	{
		$ref = new \ReflectionFunction($callable);
		$params = $ref->getParameters();
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
		$type = $type instanceof \ReflectionNamedType ? $type->getName() : $type->__toString();
		$class = new \ReflectionClass($type);
		if(!$class->isSubclassOf("Phpcraft\\Event"))
		{
			throw new InvalidArgumentException("Callable's parameter type needs to be a decendant of \\Phpcraft\\Event.");
		}
		$this->event_handlers[$type] = [
			"priority" => $priority,
			"function" => $callable
		];
		return $this;
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	public function fire(Event $event)
	{
		$type = get_class($event);
		if(isset($this->event_handlers[$type]))
		{
			($this->event_handlers[$type])($event);
		}
		return $event->cancelled;
	}
}
