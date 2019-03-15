<?php
namespace Phpcraft;
class NbtInt extends NbtTag
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
	function send(Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_send($con, 3);
		}
		$con->writeInt($this->value, true);
	}

	function copy()
	{
		return new NbtInt($this->name, $this->value);
	}

	function toString()
	{
		return "{Int \"".$this->name."\": ".$this->value."}";
	}
}
