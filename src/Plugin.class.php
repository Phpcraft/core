<?php
namespace Phpcraft;
class Plugin
{
	private $name;
	/**
	 * An array mapping of event names and their handler functions.
	 * @var array $event_handlers
	 */
	public $event_handlers = [];

	/**
	 * The constructor.
	 * @param string $name The name of the plugin.
	 */
	function __construct($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns the name of the plugin.
	 * @return string The name of the plugin.
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 * Defines a function to be called to handle the given event.
	 * Only one function can be defined per event per plugin, so subsequent calls with the same event name will overwrite the previously defined function.
	 * @param string $event_name The name of the event to be handled. Use an asterisk (*) to catch all events, and a period (.) to catch all events that are not yet caught.
	 * @param function $function
	 */
	function on($event_name, $function)
	{
		$this->event_handlers[$event_name] = $function;
	}

	/**
	 * Fires the event handler for the given event with its data as parameter.
	 * @param Event $event
	 * @return boolean True if the event was cancelled.
	 */
	function fire($event)
	{
		if(isset($this->event_handlers[$event->name]))
		{
			($this->event_handlers[$event->name])($event);
		}
		else if(isset($this->event_handlers["."]))
		{
			($this->event_handlers["."])($event);
		}
		if(isset($this->event_handlers["*"]))
		{
			($this->event_handlers["*"])($event);
		}
		return $event->isCancelled();
	}
}
