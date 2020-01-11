<?php
namespace Phpcraft;
use Asyncore\stdin;
/**
 * Plain user interface, e.g. writing logs to a file.
 */
class PlainUserInterface extends UserInterface
{
	/**
	 * Note that from this point forward, STDIN is in the hands of pas, if it wasn't already, so make sure to register a "stdin_line" event handler with pas if you'd like input to work.
	 *
	 * @param string|null $title The title that the terminal window will be given or null.
	 */
	function __construct($title = null)
	{
		parent::__construct($title);
		stdin::init(null, false);
		if(Phpcraft::isWindows() && version_compare(PHP_VERSION, "7.2.0", ">=") && php_uname("r") == "10.0" && explode(" ", php_uname("v"))[1] >= 10586)
		{
			sapi_windows_vt100_support(STDOUT, true);
		}
	}

	/**
	 * Does nothing. Only available in accordance with UserInterface.
	 */
	function render(): void
	{
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
		echo $message."\n";
		return $this;
	}
}
