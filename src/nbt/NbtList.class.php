<?php
namespace Phpcraft;
use Countable;
use Iterator;
class NbtList extends NbtTag implements Iterator, Countable
{
	private $current = 0;
	/**
	 * The NBT tag type ID of children.
	 * @var integer $childType
	 * @see NbtTag
	 */
	public $childType;
	/**
	 * The child tags of the list.
	 * @var array $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param integer $childType The NBT Tag Type of children.
	 * @param $children NbtTag[] The child tags of the list.
	 */
	public function __construct(string $name, int $childType, array $children = [])
	{
		$this->name = $name;
		$this->childType = $childType;
		$this->children = $children;
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
			$this->_write($con, 9);
		}
		$con->writeByte($this->childType);
		$con->writeInt(count($this->children), true);
		foreach($this as $child)
		{
			$child->write($con, true);
		}
		return $con;
	}

	public function copy()
	{
		return new NbtList($this->name, $this->childType, $this->children);
	}

	public function __toString()
	{
		$str = "{List \"".$this->name."\":";
		foreach($this as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
	}

	public function current()
	{
		return $this->children[$this->current];
	}

	public function next()
	{
		$this->current++;
	}

	public function key()
	{
		return $this->current;
	}

	public function valid()
	{
		return $this->current < count($this->children);
	}

	public function rewind()
	{
		$this->current = 0;
	}

	public function count()
	{
		return count($this->children);
	}
}
