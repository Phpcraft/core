<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtByte extends NbtTag
{
	const ORD = 1;
	/**
	 * The value of this tag.
	 *
	 * @var integer $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param integer $value The value of this tag.
	 */
	function __construct(string $name, int $value = 0)
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 *
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	function write(Connection $con, bool $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con->writeByte($this->value, true);
		return $con;
	}

	function copy()
	{
		return new NbtByte($this->name, $this->value);
	}

	function __toString()
	{
		return "{Byte \"".$this->name."\": ".$this->value."}";
	}
}
