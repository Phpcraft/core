<?php
namespace Phpcraft\Nbt;
use GMP;
use Phpcraft\Connection;
class NbtIntArray extends NbtTag
{
	/**
	 * The integers in the array.
	 *
	 * @var array $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param $children GMP[] The integers in the array.
	 */
	public function __construct(string $name, array $children = [])
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
	public function write(Connection $con, bool $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 11);
		}
		$con->writeInt(count($this->children), true);
		foreach($this->children as $child)
		{
			$con->writeInt($child);
		}
		return $con;
	}

	public function copy()
	{
		return new NbtIntArray($this->name, $this->children);
	}

	public function __toString()
	{
		$str = "{IntArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
		}
		return $str."}";
	}
}
