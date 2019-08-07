<?php
namespace Phpcraft\Nbt;
use GMP;
use Phpcraft\Connection;
class NbtInt extends NbtTag
{
	const ORD = 3;
	/**
	 * The value of this tag.
	 *
	 * @var GMP $value
	 */
	public $value;

	/**
	 * @param string $name The name of this tag.
	 * @param GMP|string|integer $value The value of this tag.
	 */
	function __construct(string $name, $value = 0)
	{
		$this->name = $name;
		if(!$value instanceof GMP)
		{
			$value = gmp_init($value);
		}
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
		$con->writeInt($this->value, true);
		return $con;
	}

	function copy(): NbtTag
	{
		return new NbtInt($this->name, $this->value);
	}

	function __toString(): string
	{
		return "{Int \"".$this->name."\": ".$this->value."}";
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
		return ($inList || !$this->name ? "" : self::stringToSNBT($this->name).($fancy ? ": " : ":")).gmp_strval($this->value);
	}
}
