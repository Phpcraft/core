<?php
namespace Phpcraft\NBT;
use DomainException;
use Phpcraft\
{Connection, Exception\IOException};
/**
 * The base class for NBT (named binary tag).
 *
 * @see Connection::readNBT
 */
abstract class NBT
{
	const ORD = null;
	/**
	 * The name of this tag.
	 *
	 * @var string $name
	 */
	public $name;

	/**
	 * Reads NBT data from a binary string.
	 *
	 * @param string $nbt
	 * @return NBT
	 * @throws IOException
	 */
	static function fromString(string $nbt): NBT
	{
		$con = new Connection();
		$con->read_buffer = $nbt;
		return $con->readNBT();
	}

	/**
	 * Reads NBT data from an SNBT string.
	 *
	 * @param string $snbt
	 * @param bool $inList Ignore this parameter.
	 * @return NBT
	 */
	static function fromSNBT(string $snbt, bool $inList = false): NBT
	{
		//$name_chars = array_merge(range("a", "z"), range("A", "Z"), range("0", "9"), ["-", "_"]);
		$string_type = 0;
		$escape = false;
		$name = null;
		$value = "";
		$chars = str_split($snbt);
		$l = strlen($snbt);
		for($i = 0; $i < $l; $i++)
		{
			if($escape)
			{
				$value .= $chars[$i];
				$escape = false;
				continue;
			}
			if($chars[$i] == ":" && $name === null)
			{
				if($inList)
				{
					throw new DomainException("SNBT List Item can't have a name");
				}
				$name = self::stringFromSNBT(trim($value));
				$value = "";
				continue;
			}
			if($chars[$i] == "\\")
			{
				if($string_type)
				{
					$escape = true;
				}
			}
			else if($chars[$i] == "\"")
			{
				if($string_type == 0)
				{
					$string_type = 1;
				}
				else if($string_type == 1)
				{
					$string_type = 0;
				}
			}
			else if($chars[$i] == "'")
			{
				if($string_type == 0)
				{
					$string_type = 2;
				}
				else if($string_type == 2)
				{
					$string_type = 0;
				}
			}
			if($name === null && ($chars[$i] == "{" || $chars[$i] == "["))
			{
				$name = "";
			}
			$value .= $chars[$i];
		}
		if($name === null)
		{
			$name = "";
		}
		$value = trim($value);
		if(substr($value, 0, 1) == "{")
		{
			if(substr($value, -1) != "}")
			{
				throw new DomainException("Invalid SNBT Compound: ".$value);
			}
			return new CompoundTag($name, self::parseSNBTArray(substr($value, 1, -1), false));
		}
		else if(substr($value, 0, 1) == "[")
		{
			$array = substr($value, 2, 1) == ";";
			if(substr($value, -1) != "]")
			{
				throw new DomainException("Invalid SNBT ".($array ? "Array" : "List").": ".$value);
			}
			if($array)
			{
				switch(strtolower(substr($value, 1, 2)))
				{
					case "b;":
						$nums = [];
						foreach(explode(",", substr($value, 3, -1)) as $num)
						{
							$num = trim($num);
							if(strtolower(substr($num, -1)) == "b")
							{
								$num = substr($num, 0, -1);
							}
							if(!is_numeric($num) || $num < -128 || $num > 127)
							{
								throw new DomainException("Invalid Item in SNBT Byte Array: ".$num);
							}
							array_push($nums, $num);
						}
						return new ByteArrayTag($name, $nums);
					case "i;":
						$nums = [];
						foreach(explode(",", substr($value, 3, -1)) as $num)
						{
							$num = trim($num);
							if(!is_numeric($num))
							{
								throw new DomainException("Invalid Item in SNBT Int Array: ".$num);
							}
							$num = gmp_init($num);
							if(gmp_cmp($num, "-2147483648‬") < 0 || gmp_cmp($num, "2147483647") > 0)
							{
								throw new DomainException("Invalid Item in SNBT Int Array: ".gmp_strval($num));
							}
							array_push($nums, $num);
						}
						return new IntArrayTag($name, $nums);
					case "l;":
						$nums = [];
						foreach(explode(",", substr($value, 3, -1)) as $num)
						{
							$num = trim($num);
							if(strtolower(substr($num, -1)) == "l")
							{
								$num = substr($num, 0, -1);
							}
							if(!is_numeric($num))
							{
								throw new DomainException("Invalid Item in SNBT Long Array: ".$num);
							}
							$num = gmp_init($num);
							if(gmp_cmp($num, "-9223372036854775808‬") < 0 || gmp_cmp($num, "9223372036854775807‬") > 0)
							{
								throw new DomainException("Invalid Item in SNBT Long Array: ".gmp_strval($num));
							}
							array_push($nums, $num);
						}
						return new LongArrayTag($name, $nums);
					default:
						throw new DomainException("Invalid SNBT Array: ".$value);
				}
			}
			else
			{
				$children = self::parseSNBTArray(substr($value, 1, -1), true);
				$type = NBT::ORD;
				$c = count($children);
				if($c > 0)
				{
					$type = $children[0]::ORD;
					for($i = 1; $i < $c; $i++)
					{
						if($children[$i]::ORD != $type)
						{
							throw new DomainException("Unexpected ".get_class($children[$i])." in SNBT List of ".get_class($children[0]));
						}
					}
				}
				return new ListTag($name, $type, $children);
			}
		}
		else if(is_numeric($value))
		{
			return new IntTag($name, gmp_init($value));
		}
		else if(strtolower(substr($value, -1)) == "b" && is_numeric(substr($value, 0, -1)))
		{
			return new ByteTag($name, intval(substr($value, 0, -1)));
		}
		else if(strtolower(substr($value, -1)) == "s" && is_numeric(substr($value, 0, -1)))
		{
			return new ShortTag($name, intval(substr($value, 0, -1)));
		}
		else if(strtolower(substr($value, -1)) == "l" && is_numeric(substr($value, 0, -1)))
		{
			return new LongTag($name, gmp_init(substr($value, 0, -1)));
		}
		else if(strtolower(substr($value, -1)) == "f" && is_numeric(substr($value, 0, -1)))
		{
			return new FloatTag($name, floatval(substr($value, 0, -1)));
		}
		else if(strtolower(substr($value, -1)) == "d" && is_numeric(substr($value, 0, -1)))
		{
			return new DoubleTag($name, floatval(substr($value, 0, -1)));
		}
		return new StringTag($name, self::stringFromSNBT($value));
	}

	/**
	 * @param string $snbt
	 * @return string
	 */
	static function stringFromSNBT(string $snbt): string
	{
		if(substr($snbt, 0, 1) == "\"")
		{
			if(substr($snbt, -1) == "\"")
			{
				return str_replace("\\\\", "\\", str_replace("\\\"", "\"", substr($snbt, 1, -1)));
			}
		}
		else if(substr($snbt, 0, 1) == "'")
		{
			if(substr($snbt, -1) == "'")
			{
				return str_replace("\\\\", "\\", str_replace("\\'", "'", substr($snbt, 1, -1)));
			}
		}
		else if(self::isValidBareString($snbt))
		{
			return $snbt;
		}
		throw new DomainException("Invalid SNBT string: ".$snbt);
	}

	/**
	 * @param string $string
	 * @return bool
	 */
	static function isValidBareString(string $string): bool
	{
		return preg_match("/^[a-zA-Z0-9\-_]+$/m", $string) && !is_numeric($string);
	}

	/**
	 * @param string $snbt
	 * @param bool $list
	 * @return array
	 */
	protected static function parseSNBTArray(string $snbt, bool $list): array
	{
		$items = [];
		$string_type = 0;
		$depth = 0;
		$escape = false;
		$item = "";
		$chars = str_split($snbt);
		$l = strlen($snbt);
		for($i = 0; $i < $l; $i++)
		{
			if($escape)
			{
				$item .= $chars[$i];
				$escape = false;
				continue;
			}
			if($string_type == 0)
			{
				if($chars[$i] == ",")
				{
					if($depth == 0)
					{
						$item = trim($item);
						if($item != "")
						{
							array_push($items, self::fromSNBT($item, $list));
						}
						$item = "";
						continue;
					}
				}
				if($chars[$i] == "{")
				{
					$depth++;
				}
				else if($chars[$i] == "}")
				{
					if(--$depth < 0)
					{
						throw new DomainException("Unexpected }");
					}
				}
				else if($chars[$i] == "[")
				{
					$depth++;
				}
				else if($chars[$i] == "]")
				{
					if(--$depth < 0)
					{
						throw new DomainException("Unexpected ]");
					}
				}
			}
			else if($chars[$i] == "\\")
			{
				$escape = true;
			}
			if($chars[$i] == "\"")
			{
				if($string_type == 0)
				{
					$string_type = 1;
				}
				else if($string_type == 1)
				{
					$string_type = 0;
				}
			}
			else if($chars[$i] == "'")
			{
				if($string_type == 0)
				{
					$string_type = 2;
				}
				else if($string_type == 2)
				{
					$string_type = 0;
				}
			}
			$item .= $chars[$i];
		}
		$item = trim($item);
		if($item != "")
		{
			array_push($items, self::fromSNBT($item, $list));
		}
		return $items;
	}

	/**
	 * @param string $string
	 * @return string
	 */
	static function stringToSNBT(string $string): string
	{
		if(self::isValidBareString($string))
		{
			return $string;
		}
		else if(strpos($string, "\"") === false)
		{
			return "\"$string\"";
		}
		else if(strpos($string, "'") === false)
		{
			return "'$string'";
		}
		else
		{
			return "\"".str_replace("\"", "\\\"", str_replace("\\", "\\\\", $string))."\"";
		}
	}

	/**
	 * @param string $string
	 * @return string
	 */
	protected static function indentString(string $string): string
	{
		$str = "";
		foreach(explode("\n", $string) as $line)
		{
			if($line != "")
			{
				$str .= "\t$line\n";
			}
		}
		return rtrim($str);
	}

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 *
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	abstract function write(Connection $con, bool $inList = false): Connection;

	/**
	 * @return static
	 */
	abstract function copy();

	abstract function __toString();

	/**
	 * Returns the NBT data in SNBT (stringified NBT) format, as used in commands.
	 *
	 * @param bool $fancy
	 * @param boolean $inList Ignore this parameter.
	 * @return string
	 */
	abstract function toSNBT(bool $fancy = false, bool $inList = false): string;

	protected function _write(Connection $con)
	{
		assert(static::ORD !== null);
		$con->writeByte(static::ORD);
		$con->writeShort(strlen($this->name));
		$con->writeRaw($this->name);
	}
}
