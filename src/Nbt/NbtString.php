<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtString extends NbtTag
{
	const ORD = 8;
	/**
	 * The value of this tag.
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param string $value The value of this tag.
	 */
	public function __construct(string $name, string $value = "")
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
	public function write(Connection $con, bool $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con->writeShort(strlen($this->value));
		$con->writeRaw($this->value);
		return $con;
	}

	public function copy()
	{
		return new NbtString($this->name, $this->value);
	}

	public function __toString()
	{
		return "{String \"".$this->name."\": ".$this->value."}";
	}
}
