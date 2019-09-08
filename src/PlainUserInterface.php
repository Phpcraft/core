<?php
namespace Phpcraft;
use hellsh\pai;
/**
 * Plain user interface, e.g. writing logs to a file.
 */
class PlainUserInterface
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
	 * @return string If $accept_input is true, we're not on Windows, and the user has submitted a line, the return will be that line. Otherwise, it will be null.
	 */
	function render(bool $accept_input = false)
	{
		return (($accept_input && pai::hasLine()) ? pai::getLine() : null);
	}

	/**
	 * Prints a message.
	 *
	 * @param string $message
	 * @return $this
	 */
	function append(string $message)
	{
		return $this->add($message);
	}

	/**
	 * Prints a message.
	 *
	 * @param string $message
	 * @return $this
	 */
	function add(string $message)
	{
		echo "\x1B[m{$message}\n\e[m";
		return $this;
	}
}
