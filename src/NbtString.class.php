<?php
namespace Phpcraft;
class NbtString extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var string $value
	 */
	public $value;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param string $value The value of this tag.
	 */
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @copydoc NbtTag::write
	 */
	public function write(Connection $con, $inList = false)
	{
		if(!$inList)
		{
			$this->_write($con, 8);
		}
		$con->writeShort(strlen($this->value));
		$con->writeRaw($this->value);
		return $con;
	}

	public function copy()
	{
		return new NbtString($this->name, $this->value);
	}

	public function toString()
	{
		return "{String \"".$this->name."\": ".$this->value."}";
	}
}
