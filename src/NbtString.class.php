<?php
namespace Phpcraft;
class NbtString extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var string $value
	 */
	public $value;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param string $value The value of this tag.
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
			$this->_send($con, 8);
		}
		$con->writeShort(strlen($this->value));
		$con->writeRaw($this->value);
	}

	function copy()
	{
		return new NbtString($this->name, $this->value);
	}

	function toString()
	{
		return "{String \"".$this->name."\": ".$this->value."}";
	}
}
