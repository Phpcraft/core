<?php
namespace Phpcraft;
/** The exception thrown by Phpcraft functions if nessary metadata is missing. */
class MissingMetadataException extends \Exception
{
	/**
	 * @param string $message The error message.
	 */
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}
