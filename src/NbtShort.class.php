<?php
namespace Phpcraft;
class NbtShort extends NbtTag
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
			$this->_send($con, 2);
		}
		$con->writeShort($this->value);
	}

	function copy()
	{
		return new NbtShort($this->name, $this->value);
	}

	function toString()
	{
		return "{Short \"".$this->name."\": ".$this->value."}";
	}
}
