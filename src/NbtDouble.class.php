<?php
namespace Phpcraft;
class NbtDouble extends NbtTag
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
			$this->_send($con, 6);
		}
		$con->writeDouble($this->value, true);
	}

	function copy()
	{
		return new NbtDouble($this->name, $this->value);
	}

	function toString()
	{
		return "{Double \"".$this->name."\": ".$this->value."}";
	}
}
