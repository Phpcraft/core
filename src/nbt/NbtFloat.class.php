<?php
namespace Phpcraft;
class NbtFloat extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var float $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param float $value The value of this tag.
	 */
	public function __construct(string $name, float $value = 0)
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
	public function write(Connection $con, bool $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 5);
		}
		$con->writeFloat($this->value);
		return $con;
	}

	public function copy()
	{
		return new NbtFloat($this->name, $this->value);
	}

	public function __toString()
	{
		return "{Float \"".$this->name."\": ".$this->value."}";
	}
}
