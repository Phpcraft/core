<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
class FloatProvider extends ArgumentProvider
{
	function __construct(CommandSender &$sender, string $arg)
	{
		if(!is_numeric($arg))
		{
			throw new InvalidArgumentException("{$arg} is not a valid float");
		}
		$this->value = floatval($arg);
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function write(Connection $con): void
	{
		$con->writeString("brigadier:double");
		$con->writeByte(0);
	}

	/**
	 * @return float
	 */
	function getValue(): float
	{
		return $this->value;
	}
}
