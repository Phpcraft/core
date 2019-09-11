<?php
namespace Phpcraft\Command;
abstract class StringArgument
{
	public $value;

	function __construct(string $value)
	{
		$this->value = $value;
	}
}
