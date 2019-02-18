<?php
namespace Phpcraft;
class NbtCompound extends NbtTag
{
	/**
	 * The child tags of the compound.
	 * @var array $children
	 */
	public $children;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param array $children The child tags of the compound.
	 */
	function __construct($name, $children = [])
	{
		$this->name = $name;
		$this->children = $children;
	}

	/**
	 * Gets a child of the compound by name.
	 * Only the first child with a matching name will be returned.
	 * Null if not found.
	 * @return NbtTag
	 */
	function getChild($name)
	{
		foreach($this->children as $child)
		{
			if($child->name == $name)
			{
				return $child;
			}
		}
		return null;
	}

	/**
	 * @copydoc NbtTag::send
	 */
	function send(\Phpcraft\Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_send($con, 10);
		}
		foreach($this->children as $child)
		{
			$child->send($con);
		}
		$con->writeByte(0);
	}

	function copy()
	{
		return new NbtCompound($this->children);
	}

	function toString()
	{
		$str = "{Compound \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child->toString();
		}
		return $str."}";
	}
}
