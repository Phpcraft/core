<?php
namespace Phpcraft\Exception;
use Exception;
/** The exception thrown by Phpcraft functions if there was an error with an I/O operation. */
class IOException extends Exception
{
	/**
	 * @param string $message The error message.
	 */
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}
