<?php
namespace Phpcraft\Nbt;
use GMP;
use Phpcraft\Connection;
class NbtIntArray extends NbtListTag
{
	const ORD = 11;

	/**
	 * @param string $name The name of this tag.
	 * @param array<GMP> $children The integers in the array.
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
	function write(Connection $con, bool $inList = false): Connection
	{
		if(!$inList)
		{
			$this->_write($con);
		}
		$con->writeInt(count($this->children));
		foreach($this->children as $child)
		{
			$con->writeInt($child);
		}
		return $con;
	}

	function copy(): NbtTag
	{
		return new NbtIntArray($this->name, $this->children);
	}

	function __toString()
	{
		$str = "{IntArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
		}
		return $str."}";
	}

	/**
	 * Returns the NBT tag in SNBT (stringified NBT) format, as used in commands.
	 *
	 * @param bool $fancy
	 * @param boolean $inList Ignore this parameter.
	 * @return string
	 */
	function toSNBT(bool $fancy = false, bool $inList = false): string
	{
		return self::gmpListToSNBT($fancy, $inList, "I");
	}
}
