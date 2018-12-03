<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
/** A plain user interface, using only STDIN & STDOUT without anything fancy. */
class PlainUserInterface
{
	protected $stdin;

	/**
	 * Returns an array of dependencies required for spinning up a UI which are missing on the system.
	 * To spin up a plain UI, users need tput, which _should_ be available because WINNT is no longer supported.
	 * Regardless, make sure the return of this function is an empty array before you initalize a PlainUserInterface.
	 * @return array
	 */
	static function getMissingDependencies()
	{
		$dependencies = [];
		$res = trim(shell_exec("tput cols"));
		if($res != intval($res))
		{
			array_push($dependencies, "tput");
		}
		return $dependencies;
	}

	function __construct()
	{
		$this->stdin = fopen("php://stdin", "r");
		stream_set_blocking($this->stdin, false);
		set_error_handler(function($severity, $message, $file, $line)
		{
			if(error_reporting() & $severity)
			{
				$this->add("{$message} at {$file}:{$line}")->render();
			}
		});
		set_exception_handler(function($e)
		{
			$this->add("{$e->getMessage()} (".get_class($e).") at {$e->getFile()}:{$e->getLine()}")->render();
		});
	}

	/**
	 * Renders the UI.
	 * @param boolean $instant Set this to false when you're calling render in a loop and are ready to handle user input.
	 * @return string If $instant is false and the user has submitted a line, the return will be that line; otherwise it will be null.
	 */
	function render($instant = true)
	{
		$read = [$this->stdin];
		$null = null;
		if(stream_select($read, $null, $null, 0))
		{
			return trim(fgets($this->stdin));
		}
	}

	/**
	 * Disposes of the UI.
	 * @return void
	 */
	function dispose()
	{
		fclose($this->stdin);
		readline_callback_handler_remove();
	}

	/**
	 * Prints a message.
	 * @return $this
	 */
	function add($message)
	{
		echo "{$message}\n\x1B[97;40m";
		return $this;
	}

	/**
	 * Prints a message.
	 * @return $this
	 */
	function append($message)
	{
		return $this->add($message);
	}
}
