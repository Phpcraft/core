<?php
namespace Phpcraft;
class NbtByteArray extends NbtTag
{
	/**
	 * The bytes in the array.
	 * @var array $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param array $children The bytes in the array.
	 */
	public function __construct($name, $children = [])
	{
		$this->name = $name;
		$this->children = $children;
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
			$this->_write($con, 7);
		}
		$con->writeInt(count($this->children), true);
		foreach($this->children as $child)
		{
			$con->writeByte($child);
		}
		return $con;
	}

	public function copy()
	{
		return new NbtByteArray($this->name, $this->children);
	}

	public function toString()
	{
		$str = "{ByteArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".dechex($child);
		}
		return $str."}";
	}
}
