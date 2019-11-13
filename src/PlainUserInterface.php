<?php
namespace Phpcraft;
use hellsh\pai;
/**
 * Plain user interface, e.g. writing logs to a file.
 */
class PlainUserInterface extends UserInterface
{
	/**
	 * Note that from this point forward, STDIN is in the hands of pai.
	 */
	function __construct()
	{
		pai::init();
		if(Phpcraft::isWindows() && version_compare(PHP_VERSION, "7.2.0", ">=") && php_uname("r") == "10.0" && explode(" ", php_uname("v"))[1] >= 10586)
		{
			/** @noinspection PhpUndefinedFunctionInspection */
			sapi_windows_vt100_support(STDOUT, true);
		}
	}

	/**
	 * Renders the UI.
	 *
	 * @param boolean $accept_input Set to true if you are looking for a return value.
	 * @return string|null If $accept_input is true and the user has submitted a line, the return will be that line. Otherwise, it will be null.
	 */
	function render(bool $accept_input = false): ?string
	{
		return (($accept_input && pai::hasLine()) ? pai::getLine() : null);
	}

	/**
	 * Prints the given message.
	 * Only available in accordance with UserInterface, appending messages is not supported here.
	 *
	 * @param string $message
	 * @return PlainUserInterface $this
	 */
	function append(string $message): PlainUserInterface
	{
		return $this->add($message);
	}

	/**
	 * Prints the given message.
	 *
	 * @param string $message
	 * @return PlainUserInterface $this
	 */
	function add(string $message): PlainUserInterface
	{
		echo "\e[m{$message}\n\e[m";
		return $this;
	}
}
