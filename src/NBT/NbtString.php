<?php
namespace Phpcraft\NBT;
use Phpcraft\Connection;
class NbtString extends NBT
{
	const ORD = 8;
	/**
	 * The value of this tag.
	 *
	 * @var string $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param string $value The value of this tag.
	 */
	function __construct(string $name, string $value = "")
	{
		$this->name = $name;
		$this->value = $value;
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
		$con->writeShort(strlen($this->value));
		$con->writeRaw($this->value);
		return $con;
	}

	function copy(): NBT
	{
		return new NbtString($this->name, $this->value);
	}

	function __toString()
	{
		return "{String \"".$this->name."\": ".$this->value."}";
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).self::stringToSNBT($this->value);
	}
}
