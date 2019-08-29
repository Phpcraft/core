<?php
namespace Phpcraft\Command;
use DomainException;
class FloatArgumentProvider extends ArgumentProvider
{
	/**
	 * @var integer $value
	 */
	private $value;

	public function __construct(string $arg)
	{
		if(!is_numeric($arg))
		{
			throw new DomainException("{$arg} is not a valid float");
		}
		$this->value = floatval($arg);
	}

	function getValue(): float
	{
		return $this->value;
	}
}
