<?php
namespace Phpcraft;
class Event
{
	/**
	 * The name of the event.
	 * @var string $name
	 */
	public $name;
	/**
	 * Event data.
	 * @var array $data
	 */
	public $data;
	private $cancelled = false;

	/**
	 * The constructor.
	 * @param string $name
	 * @param array $data
	 * @param boolean True if you'd like the event to be cancelable.
	 */
	function __construct($name, $data = [])
	{
		$this->name = $name;
		$this->data = $data;
	}

	/**
	 * Tells you if the event was cancelled.
	 * @return boolean
	 */
	function isCancelled()
	{
		return $this->cancelled;
	}

	/**
	 * Cancels the event.
	 */
	function cancel()
	{
		$this->cancelled = true;
	}
}
