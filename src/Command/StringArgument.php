<?php
namespace Phpcraft\Command;
abstract class StringArgument
{
	/**
	 * @var string $value
	 */
	public $value;

	function __construct(string $value)
	{
		$this->value = $value;
	}

	function __toString()
	{
		return $this->value;
	}
}
