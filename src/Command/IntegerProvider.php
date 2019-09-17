<?php
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
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

	static function write(Connection $con)
	{
		$con->writeString("brigadier:integer");
		$con->writeByte(0);
	}

	function getValue(): int
	{
		return $this->value;
	}
}
