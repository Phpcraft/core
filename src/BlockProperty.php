<?php
namespace Phpcraft;
class BlockProperty
{
	/**
	 * @var array<string> $values Array of possible values.
	 */
	public $values;
	/**
	 * @var int $default Index of the default value in $this-&gt;values.
	 */
	public $default;

	/**
	 * @param array<string> $values Array of possible values.
	 */
	function __construct(array $values)
	{
		$this->values = $values;
	}

	/**
	 * @return string
	 */
	function getDefaultValue(): string
	{
		return $this->values[$this->default];
	}
}
