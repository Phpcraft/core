<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
/** The class used for exceptions thrown by Phpcraft functions. */
class Exception extends \Exception
{
	/**
	 * The constructor.
	 * @param string $message The error message.
	 */
	function __construct($message)
	{
		parent::__construct($message);
	}
}