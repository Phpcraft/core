<?php
namespace Phpcraft;
class NbtCompound extends NbtTag
{
	/**
	 * The child tags of the compound.
	 * @var NbtTag[] $children
	 */
	public $children;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param NbtTag[] $children The child tags of the compound.
	 */
	function __construct($name, $children = [])
	{
		$this->name = $name;
		$this->children = $children;
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
