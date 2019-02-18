<?php
namespace Phpcraft;
class NbtFloat extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var integer $value
	 */
	public $value;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param integer $value The value of this tag.
	 */
	function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @copydoc NbtTag::send
	 */
	function send(\Phpcraft\Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_send($con, 5);
		}
		$con->writeFloat($this->value);
	}

	function copy()
	{
		return new NbtFloat($this->name, $this->value);
	}

	function toString()
	{
		return "{Float \"".$this->name."\": ".$this->value."}";
	}
}
