<?php
namespace Phpcraft\Exception;
/**
 * The exception thrown when attempting to write to a Connection with a closed stream, e.g. because the remote disconnected.
 *
 * @since 0.5.12
 */
class NoConnectionException extends IOException
{
	function __construct()
	{
		parent::__construct("Can't write to closed stream");
	}
}
