<?php
namespace Phpcraft\Command;
use DomainException;
class IntegerArgumentProvider extends ArgumentProvider
{
	/**
	 * @var integer $value
	 */
	private $value;

	public function __construct(CommandSender &$sender, string $arg)
	{
		if(!is_numeric($arg) || floor($arg) != $arg)
		{
			throw new DomainException("{$arg} is not a valid integer");
		}
		$this->value = intval($arg);
	}

	function getValue(): int
	{
		return $this->value;
	}
}
