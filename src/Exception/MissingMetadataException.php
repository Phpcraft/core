<?php
namespace Phpcraft\Exception;
use Exception;
/** The exception thrown by Phpcraft functions if nessary metadata is missing. */
class MissingMetadataException extends Exception
{
	/**
	 * @param string $message The error message.
	 */
	function __construct(string $message)
	{
		parent::__construct($message);
	}
}
