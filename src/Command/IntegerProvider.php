<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
class IntegerProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		if(!is_numeric($arg) || floor(floatval($arg)) != floatval($arg))
		{
			throw new InvalidArgumentException("{$arg} is not a valid integer");
		}
		$this->value = intval($arg);
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function write(Connection $con): void
	{
		$con->writeString("brigadier:integer");
		$con->writeByte(0);
	}

	/**
	 * @return int
	 */
	function getValue(): int
	{
		return $this->value;
	}
}
