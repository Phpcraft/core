<?php
namespace Phpcraft;
class NbtByte extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var integer $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param integer $value The value of this tag.
	 */
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	public function write(Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 1);
		}
		$con->writeByte($this->value, true);
		return $con;
	}

	public function copy()
	{
		return new NbtByte($this->name, $this->value);
	}

	public function toString()
	{
		return "{Byte \"".$this->name."\": ".$this->value."}";
	}
}
