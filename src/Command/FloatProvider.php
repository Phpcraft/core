<?php
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
class FloatProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		if(!is_numeric($arg))
		{
			throw new InvalidArgumentException("{$arg} is not a valid float");
		}
		$this->value = floatval($arg);
	}

	static function write(Connection $con)
	{
		$con->writeString("brigadier:double");
		$con->writeByte(0);
	}

	function getValue(): float
	{
		return $this->value;
	}
}
