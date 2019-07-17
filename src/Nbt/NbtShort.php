<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtShort extends NbtTag
{
	const ORD = 2;
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
		$con->writeShort($this->value);
		return $con;
	}

	function copy()
	{
		return new NbtShort($this->name, $this->value);
	}

	function __toString()
	{
		return "{Short \"".$this->name."\": ".$this->value."}";
	}
}
