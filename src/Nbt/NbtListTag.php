<?php
namespace Phpcraft\Nbt;
use ArrayAccess;
use Countable;
use Iterator;
abstract class NbtListTag extends NbtTag implements Iterator, Countable, ArrayAccess
{
	/**
	 * The children of the list.
	 *
	 * @var array $children
	 */
	public $children;
	private $current = 0;

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

	function offsetExists($offset)
	{
		return array_key_exists($offset, $this->children);
	}

	function offsetGet($offset)
	{
		return @$this->children[$offset];
	}

	function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			array_push($this->children, $value);
		}
		else
		{
			$this->children[$offset] = $value;
		}
	}

	function offsetUnset($offset)
	{
		unset($this->children[$offset]);
	}

	function count()
	{
		return count($this->children);
	}

	protected function gmpListToSNBT(bool $fancy, bool $inList, string $type_char)
	{
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[".$type_char.";".($fancy ? " " : "");
		$c = count($this->children);
		for($i = 0; $i < $c; $i++)
		{
			$snbt .= gmp_strval($this->children[$i]).($i == $c - 1 ? "" : ($fancy ? ", " : ","));
		}
		return $snbt."]";
	}
}
