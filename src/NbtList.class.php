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
	 * The constructor.
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
	 * @copydoc NbtTag::write
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

	public function toString()
	{
		$str = "{List \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child->toString();
		}
		return $str."}";
	}
}
