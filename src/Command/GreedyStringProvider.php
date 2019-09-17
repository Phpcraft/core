<?php
namespace Phpcraft\Command;
use Phpcraft\Connection;
class GreedyStringProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
	}

	static function write(Connection $con)
	{
		$con->writeString("brigadier:string");
		$con->writeVarInt(2); // GREEDY_PHRASE
	}

	function getValue(): GreedyString
	{
		return new GreedyString($this->value);
	}

	function acceptsMore(): bool
	{
		return true;
	}

	function acceptNext(string $arg)
	{
		$this->value .= " ".$arg;
	}
}
