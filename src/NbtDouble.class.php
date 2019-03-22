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
	 * @copydoc NbtTag::write
	 */
	function write(Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 6);
		}
		$con->writeDouble($this->value);
		return $con;
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
