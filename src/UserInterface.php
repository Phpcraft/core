<?php
namespace Phpcraft;
abstract class UserInterface
{
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
