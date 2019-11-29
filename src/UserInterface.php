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

	/**
	 * Renders the user interface.
	 *
	 * @param boolean $accept_input Set to true if you are looking for a return value.
	 * @return string|null If $accept_input is true and the user has submitted a line, the return will be that line. Otherwise, it will be null.
	 */
	abstract function render(bool $accept_input = false): ?string;

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
