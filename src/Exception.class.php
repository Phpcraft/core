<?php
namespace Phpcraft;
/** The class used for exceptions thrown by Phpcraft functions. */
class Exception extends \Exception
{
	/**
	 * The constructor.
	 * @param string $message The error message.
	 */
	public function __construct($message)
	{
		parent::__construct($message);
	}
}