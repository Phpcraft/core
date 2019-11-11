<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
class SingleWordStringProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
	}

	function getValue(): SingleWordString
	{
		return new SingleWordString($this->value);
	}
}
