<?php
namespace Phpcraft;
class Plugin
{
	private $name;
	/**
	 * An array mapping of event names to an object with a function and priority.
	 * @var array $event_handlers
	 */
	public $event_handlers = [];

	/**
	 * @param string $name The name of the plugin.
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the name of the plugin.
	 * @return string The name of the plugin.
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 * Only one function can be defined per event per plugin, so subsequent calls with the same event name will overwrite the previously defined function.
	 * @param string $event_name The name of the event to be handled. Use an asterisk (*) to catch all events, and a period (.) to catch all uncaught events.
	 * @param callable $function
	 * @param integer $priority The priority of the event handler. The higher the priority, the earlier it will be executed. Use a high value if you plan to cancel the event.
	 * @return Plugin $this
	 */
	public function on($event_name, $function, $priority = Event::PRIORITY_NORMAL)
	{
		$this->event_handlers[$event_name] = [
			"priority" => $priority,
			"function" => $function
		];
		return $this;
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	public function fire($event)
	{
		if(isset($this->event_handlers[$event->name]))
		{
			($this->event_handlers[$event->name]["function"])($event);
		}
		else if(isset($this->event_handlers["."]))
		{
			($this->event_handlers["."]["function"])($event);
		}
		if(isset($this->event_handlers["*"]))
		{
			($this->event_handlers["*"]["function"])($event);
		}
		return $event->isCancelled();
	}
}
