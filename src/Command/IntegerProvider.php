<?php
namespace Phpcraft\Command;
use InvalidArgumentException;
class IntegerProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		if(!is_numeric($arg) || floor($arg) != $arg)
		{
			throw new InvalidArgumentException("{$arg} is not a valid integer");
		}
		$this->value = intval($arg);
	}

	function getValue(): int
	{
		return $this->value;
	}
}
