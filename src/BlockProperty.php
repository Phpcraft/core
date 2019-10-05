<?php
namespace Phpcraft;
class BlockProperty
{
	/**
	 * @var $values string[] Array of possible values.
	 */
	public $values;
	/**
	 * @var int $default Index of the default value in $this-&gt;values.
	 */
	public $default;

	/**
	 * @param $values string[] Array of possible values.
	 */
	function __construct(array $values)
	{
		$this->values = $values;
	}

	function getDefaultValue(): string
	{
		return $this->values[$this->default];
	}
}
