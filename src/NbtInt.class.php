<?php
namespace Phpcraft;
class NbtInt extends NbtTag
{
	/**
	 * The value of this tag.
	 * @var integer $value
	 */
	public $value;

	/**
	 * The constructor.
	 * @param string $name The name of this tag.
	 * @param integer $value The value of this tag.
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
			$this->_write($con, 3);
		}
		$con->writeInt($this->value, true);
		return $con;
	}

	public function copy()
	{
		return new NbtInt($this->name, $this->value);
	}

	public function toString()
	{
		return "{Int \"".$this->name."\": ".$this->value."}";
	}
}
