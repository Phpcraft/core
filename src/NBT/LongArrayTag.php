<?php
namespace Phpcraft\NBT;
use GMP;
use Phpcraft\Connection;
class LongArrayTag extends AbstractListTag
{
	const ORD = 12;

	/**
	 * @param string $name The name of this tag.
	 * @param array<GMP> $children The longs in the array.
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
			$con->writeLong($child);
		}
		return $con;
	}

	function __toString()
	{
		$str = "{LongArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
		}
		return $str."}";
	}

	function copy(): LongArrayTag
	{
		return new LongArrayTag($this->name, $this->children);
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
		return self::gmpListToSNBT($fancy, $inList, "L");
	}
}
