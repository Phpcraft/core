<?php
namespace Phpcraft\Nbt;
use Countable;
use Iterator;
use Phpcraft\Connection;
class NbtList extends NbtListTag implements Iterator, Countable
{
	const ORD = 9;
	/**
	 * The NBT tag type ID of children.
	 *
	 * @var integer $childType
	 * @see NbtTag::ORD
	 */
	public $childType;

	/**
	 * @param string $name The name of this tag.
	 * @param integer $childType The NBT Tag Type of children.
	 * @param $children NbtTag[] The child tags of the list.
	 */
	function __construct(string $name, int $childType, array $children = [])
	{
		$this->name = $name;
		$this->childType = $childType;
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
		$con->writeByte($this->childType);
		$con->writeInt(count($this->children), true);
		foreach($this as $child)
		{
			$child->write($con, true);
		}
		return $con;
	}

	function copy(): NbtTag
	{
		return new NbtList($this->name, $this->childType, $this->children);
	}

	function __toString(): string
	{
		$str = "{List \"".$this->name."\":";
		foreach($this as $child)
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
		$snbt = ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":"))."[".($fancy ? "\n" : "");
		$c = count($this->children) - 1;
		if($fancy)
		{
			for($i = 0; $i <= $c; $i++)
			{
				$snbt .= self::indentString($this->children[$i]->toSNBT(true, true)).($i == $c ? "" : ",")."\n";
			}
		}
		else
		{
			for($i = 0; $i <= $c; $i++)
			{
				$snbt .= $this->children[$i]->toSNBT(false, true).($i == $c ? "" : ",");
			}
		}
		return $snbt."]";
	}
}
