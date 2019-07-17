<?php
namespace Phpcraft\Nbt;
use Countable;
use Iterator;
use Phpcraft\Connection;
class NbtList extends NbtTag implements Iterator, Countable
{
	const ORD = 9;
	/**
	 * The NBT tag type ID of children.
	 *
	 * @var integer $childType
	 * @see NbtTag
	 */
	public $childType;
	/**
	 * The child tags of the list.
	 *
	 * @var array $children
	 */
	public $children;
	private $current = 0;

	/**
	 * @param string $name The name of this tag.
	 * @param integer $childType The NBT Tag Type of children.
	 * @param $children NbtTag[] The child tags of the list.
	 */
	function __construct(string $name, int $childType, array $children = [])
	{
		$this->name = $name;
		$this->childType = $childType;
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
		$con->writeByte($this->childType);
		$con->writeInt(count($this->children), true);
		foreach($this as $child)
		{
			$child->write($con, true);
		}
		return $con;
	}

	function copy()
	{
		return new NbtList($this->name, $this->childType, $this->children);
	}

	function __toString()
	{
		$str = "{List \"".$this->name."\":";
		foreach($this as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
	}

	function current()
	{
		return $this->children[$this->current];
	}

	function next()
	{
		$this->current++;
	}

	function key()
	{
		return $this->current;
	}

	function valid()
	{
		return $this->current < count($this->children);
	}

	function rewind()
	{
		$this->current = 0;
	}

	function count()
	{
		return count($this->children);
	}
}
