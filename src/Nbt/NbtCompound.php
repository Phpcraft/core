<?php
namespace Phpcraft\Nbt;
use ArrayAccess;
use Countable;
use Iterator;
use Phpcraft\Connection;
use SplObjectStorage;
class NbtCompound extends NbtTag implements Iterator, Countable, ArrayAccess
{
	const ORD = 10;
	/**
	 * The child tags of the compound.
	 *
	 * @var SplObjectStorage $children
	 */
	public $children;

	/**
	 * @param string $name The name of this tag.
	 * @param array<NbtTag> $children The child tags of the compound.
	 */
	function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = new SplObjectStorage();
		foreach($children as $child)
		{
			$this->children->attach($child);
		}
	}

	/**
	 * Returns true if the compound has a child with the given name.
	 *
	 * @param string $name
	 * @return boolean
	 */
	function hasChild(string $name)
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
		foreach($this->children as $child)
		{
			$child->write($con);
		}
		$con->writeByte(0);
		return $con;
	}

	function copy(): NbtTag
	{
		$tag = new NbtCompound($this->name);
		$tag->children->addAll($this->children);
		return $tag;
	}

	function __toString()
	{
		$str = "{Compound \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child->__toString();
		}
		return $str."}";
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
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."{".($fancy ? "\n" : "");
		$c = count($this->children) - 1;
		if($fancy)
		{
			$i = 0;
			foreach($this->children as $child)
			{
				$snbt .= self::indentString($child->toSNBT(true)).($i++ == $c ? "" : ",")."\n";
			}
		}
		else
		{
			$i = 0;
			foreach($this->children as $child)
			{
				$snbt .= $child->toSNBT().($i++ == $c ? "" : ",");
			}
		}
		return $snbt."}";
	}

	function current()
	{
		return $this->children->current();
	}

	function next()
	{
		$this->children->next();
	}

	function key()
	{
		return $this->children->current()->name;
	}

	function valid()
	{
		return $this->children->valid();
	}

	function rewind()
	{
		$this->children->rewind();
	}

	function offsetExists($offset)
	{
		return $this->offsetGet($offset) !== null;
	}

	function offsetGet($offset)
	{
		return $this->getChild($offset);
	}

	/**
	 * Gets a child of the compound by its name or null if not found.
	 *
	 * @param string $name
	 * @return NbtTag
	 */
	function getChild(string $name)
	{
		foreach($this->children as $child)
		{
			assert($child instanceof NbtTag);
			if($child->name == $name)
			{
				return $child;
			}
		}
		return null;
	}

	function offsetSet($offset, $value)
	{
		assert($value instanceof NbtTag);
		assert($offset === null || $offset === $value->name);
		$this->addChild($value);
	}

	/**
	 * Adds a child to the compound or replaces an existing one by the same name.
	 *
	 * @param NbtTag $tag
	 * @return NbtCompound $this
	 */
	function addChild(NbtTag $tag)
	{
		if($tag instanceof NbtEnd)
		{
			trigger_error("I'm not adding NbtEnd as the child of an NbtCompound because it is not a real tag and should not be treated as such.");
		}
		else
		{
			foreach($this->children as $child)
			{
				if($child->name == $tag->name)
				{
					$this->children->detach($child);
					break;
				}
			}
			$this->children->attach($tag);
		}
		return $this;
	}

	function offsetUnset($offset)
	{
		$this->children->detach($this->getChild($offset));
	}

	function count()
	{
		return $this->children->count();
	}
}
