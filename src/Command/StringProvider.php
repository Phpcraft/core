<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
/**
 * Provides a SingleWordStringArgument as PHP's native string to commands.
 */
class StringProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$this->value = $arg;
	}

	function getValue(): string
	{
		return $this->value;
	}
}
