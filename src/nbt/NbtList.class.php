<?php
namespace Phpcraft;
class NbtList extends NbtTag
{
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
	 * @param array $children The child tags of the list.
	 */
	public function __construct($name, $childType, $children = [])
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
	public function write(Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 9);
		}
		$con->writeByte($this->childType);
		$con->writeInt(count($this->children), true);
		foreach($this->children as $child)
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
		foreach($this->children as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
	}
}
