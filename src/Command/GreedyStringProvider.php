<?php
namespace Phpcraft\Command;
class GreedyStringProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
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
