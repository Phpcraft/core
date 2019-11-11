<?php
namespace Phpcraft\NBT;
use Phpcraft\Connection;
class ByteArrayTag extends AbstractListTag
{
	const ORD = 7;

	/**
	 * @param string $name The name of this tag.
	 * @param array<int> $children The bytes in the array.
	 */
	function __construct(string $name, array $children = [])
	{
		$this->name = $name;
		$this->children = $children;
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
		$con->writeInt(count($this->children));
		foreach($this->children as $child)
		{
			$con->write_buffer .= pack("c", $child);
		}
		return $con;
	}

	/**
	 * @return ByteArrayTag
	 */
	function copy(): ByteArrayTag
	{
		return new ByteArrayTag($this->name, $this->children);
	}

	function __toString()
	{
		$str = "{ByteArray \"".$this->name."\":";
		foreach($this->children as $child)
		{
			$str .= " ".dechex($child);
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
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[B;".($fancy ? " " : "");
		$c = count($this->children);
		for($i = 0; $i < $c; $i++)
		{
			$snbt .= $this->children[$i].($i == $c - 1 ? "" : ($fancy ? ", " : ","));
		}
		return $snbt."]";
	}
}
