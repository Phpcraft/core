<?php
namespace Phpcraft\Command;
use BadMethodCallException;
class UnimplementedParser extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		throw new BadMethodCallException("Unimplemented Parser");
	}

	function getValue()
	{
	}
}
