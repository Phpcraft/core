<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtByteArray extends NbtTag
{
	const ORD = 7;
	/**
	 * The bytes in the array.
	 *
	 * @var array $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param $children integer[] The bytes in the array.
	 */
	function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = $children;
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
		$con->writeInt(count($this->children), true);
		foreach($this->children as $child)
		{
			$con->writeByte($child);
		}
		return $con;
	}

	function copy()
	{
		return new NbtByteArray($this->name, $this->children);
	}

	function __toString()
	{
		$str = "{ByteArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".dechex($child);
		}
		return $str."}";
	}
}
