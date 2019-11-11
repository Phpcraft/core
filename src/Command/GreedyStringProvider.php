<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
use Phpcraft\Connection;
class GreedyStringProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function write(Connection $con): void
	{
		$con->writeString("brigadier:string");
		$con->writeVarInt(2); // GREEDY_PHRASE
	}

	/**
	 * @return GreedyString
	 */
	function getValue(): GreedyString
	{
		return new GreedyString($this->value);
	}

	/**
	 * @return bool
	 */
	function acceptsMore(): bool
	{
		return true;
	}

	/**
	 * @param string $arg
	 * @return void
	 */
	function acceptNext(string $arg): void
	{
		$this->value .= " ".$arg;
	}
}
