<?php
namespace Phpcraft;
class NbtDouble extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var float $value
	 */
	public $value;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param float $value The value of this tag.
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
			$this->_write($con, 6);
		}
		$con->writeDouble($this->value);
		return $con;
	}

	public function copy()
	{
		return new NbtDouble($this->name, $this->value);
	}

	public function toString()
	{
		return "{Double \"".$this->name."\": ".$this->value."}";
	}
}
