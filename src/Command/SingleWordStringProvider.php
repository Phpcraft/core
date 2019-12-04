<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
class SingleWordStringProvider extends ArgumentProvider
{
	function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
	}

	/**
	 * @return SingleWordString
	 */
	function getValue(): SingleWordString
	{
		return new SingleWordString($this->value);
	}
}
