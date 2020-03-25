<?php
namespace Phpcraft\Exception;
/**
 * The exception thrown when attempting to write to a Connection with a closed stream, e.g. because the remote disconnected.
 *
 * @since 0.5.12
 */
class NoConnectionException extends IOException
{
	/**
	 * @param string $message The error message.
	 */
	function __construct(string $message)
	{
		parent::__construct($message);
	}
}
