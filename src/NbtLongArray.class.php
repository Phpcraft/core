<?php
namespace Phpcraft;
class NbtLongArray extends NbtTag
{
	/**
	 * The longs in the array.
	 * @var array $children
	 */
	public $children;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param array $children The longs in the array.
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
			$this->_send($con, 12);
		}
		$con->writeInt(count($this->children), true);
		foreach($this->children as $child)
		{
			$con->writeLong($child);
		}
	}

	function copy()
	{
		return new NbtIntArray($this->name, $this->children);
	}

	function toString()
	{
		$str = "{LongArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".$child;
		}
		return $str."}";
	}
}
