<?php
namespace Phpcraft;
/** A UUID helper class. */
class Uuid
{
	/**
	 * The binary string containing the UUID.
	 * @var string $binary
	 */
	public $binary;

	/**
	 * The constructor.
	 * @param string $binary The binary string containing the UUID.
	 * @throws Exception When the given string is not a valid UUID binary string.
	 * @see Uuid::fromString
	 */
	function __construct($binary)
	{
		if(strlen($binary) != 16)
		{
			throw new Exception("Invalid UUID binary string: {$binary}");
		}
		$this->binary = $binary;
	}

	/**
	 * Returns a Uuid for the given string.
	 * @throws Exception When the given string is not a valid UUID.
	 * @return Uuid
	 */
	static function fromString($str)
	{
		$str = str_replace(["-", "{", "}"], "", $str);
		if(strlen($str) != 32)
		{
			throw new Exception("Invalid UUID: $str");
		}
		return Uuid::fromString_($str);
	}

	private static function fromString_($str)
	{
		$binary = "";
		for($i = 0; $i < 32; $i += 2)
		{
			$binary .= chr(hexdec(substr($str, $i, 2)));
		}
		return new Uuid($binary);
	}

	/**
	 * Generates a UUIDv4.
	 * @return Uuid
	 */
	static function v4()
	{
		return UUID::fromString_(sprintf("%04x%04x%04x%04x%04x%04x%04x%04x", mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), (mt_rand(0, 0x0fff) | 0x4000), (mt_rand(0, 0x3fff) | 0x8000), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)));
	}

	/**
	 * Generates a UUIDv5.
	 * @return Uuid
	 */
	static function v5($str, Uuid $namespace = null)
	{
		if(!$namespace)
		{
			$namespace = new Uuid(str_repeat(chr(0), 16));
		}
		$hash = sha1($str.$namespace->binary);
		return UUID::fromString_(sprintf("%08s%04s%04x%04x%12s", substr($hash, 0, 8), substr($hash, 8, 4), (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000, (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000, substr($hash, 20, 12)));
	}

	/**
	 * Returns the string representation of the Uuid.
	 * @param boolean $withHypens
	 * @return string
	 */
	function toString($withHypens = false)
	{
		$str = "";
		for($i = 0; $i < 16; $i++)
		{
			if($withHypens && in_array($i, [4, 6, 8, 10]))
			{
				$str .= "-";
			}
			$sec = dechex(ord(substr($this->binary, $i, 1)));
			if(strlen($sec) != 2)
			{
				$sec = "0".$sec;
			}
			$str .= $sec;
		}
		return $str;
	}

	/**
	 * Returns an integer which will always be the same given the same Uuid, but collisions are far more likely.
	 * @return integer
	 */
	function toInt()
	{
		return gmp_intval(gmp_import(substr($this->binary, 0, 2).substr($this->binary, -2)));
	}
}
