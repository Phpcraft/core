<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtCompound extends NbtTag
{
	const ORD = 10;
	/**
	 * The child tags of the compound.
	 *
	 * @var array $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param $children NbtTag[] The child tags of the compound.
	 */
	public function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = $children;
	}

	/**
	 * Gets a child of the compound by its name or null if not found.
	 *
	 * @param string $name
	 * @return NbtTag
	 */
	public function getChild(string $name)
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
	 * Returns true if the compound has a child with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasChild(string $name)
	{
		foreach($this->children as $child)
		{
			if($child->name == $name)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds a child to the compound or replaces an existing one by the same name.
	 *
	 * @param NbtTag $tag
	 * @return NbtCompound $this
	 */
	public function addChild(NbtTag $tag)
	{
		if($tag instanceof NbtEnd)
		{
			trigger_error("Ignoring NbtEnd, as it is not a valid child");
		}
		else
		{
			$i = $this->getChildIndex($tag->name);
			if($i > -1)
			{
				$this->children[$i] = $tag;
			}
			else
			{
				array_push($this->children, $tag);
			}
		}
		return $this;
	}

	/**
	 * Gets the index of a child of the compound by its name or -1 if not found.
	 *
	 * @param string $name
	 * @return integer
	 */
	public function getChildIndex(string $name)
	{
		foreach($this->children as $i => $child)
		{
			if($child->name == $name)
			{
				return $i;
			}
		}
		return -1;
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
			$this->_write($con);
		}
		foreach($this->children as $child)
		{
			$child->write($con);
		}
		$con->writeByte(0);
		return $con;
	}

	public function copy()
	{
		return new NbtCompound($this->name, $this->children);
	}

	public function __toString()
	{
		$str = "{Compound \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
	}
}
