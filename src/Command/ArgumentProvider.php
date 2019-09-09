<?php
namespace Phpcraft\Command;
/**
 * Extend this class to create a custom argument provider for Commands.
 * All declared classes will automagically be scanned and if they extends this class, it will be registered as the provider of the type that their getValue function returns.
 * User input will be given to the constructor, if it is invalid for your type, simply throw a DomainException or similar.
 * Should the type you're providing require more than one "word", feel free to override isFinished and acceptNext.
 */
abstract class ArgumentProvider
{
	abstract function __construct(CommandSender &$sender, string $arg);

	/**
	 * Does nothing and shouldn't do anything.
	 * This function is called on "native" argument providers by Command so they are forced into existence.
	 */
	static function noop()
	{
	}

	abstract function getValue();

	function isFinished(): bool
	{
		return true;
	}

	function acceptNext(string $arg)
	{
	}
}
