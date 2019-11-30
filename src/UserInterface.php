<?php
namespace Phpcraft;
abstract class UserInterface
{
	/**
	 * @param string|null $title The title that the terminal window will be given or null.
	 */
	function __construct($title = null)
	{
		if(is_string($title))
		{
			cli_set_process_title($title);
		}
	}

	abstract function render(): void;

	/**
	 * Adds a message to be printed on the user interface.
	 *
	 * @param string $message
	 * @return UserInterface $this
	 */
	abstract function add(string $message);

	/**
	 * Appends to the last message or adds a new message if not supported by the user interface.
	 *
	 * @param string $message
	 * @return UserInterface $this
	 */
	abstract function append(string $message);
}
