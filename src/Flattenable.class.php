<?php
namespace Phpcraft;
abstract class Flattenable
{
	/**
	 * The name without minecraft: prefix.
	 * @var string $name
	 */
	public $name;
	/**
	 * The post-flattening ID.
	 * @var integer $id
	 */
	public $id;
	/**
	 * The pre-flattening ID.
	 * @var integer $legacy_id
	 */
	public $legacy_id;

	/**
	 * The constructor.
	 * @param string $name The name without minecraft: prefix.
	 * @param integer $id The post-flattening ID.
	 * @param integer $legacy_id The pre-flattening ID.
	 */
	function __construct($name, $id, $legacy_id)
	{
		$this->name = $name;
		$this->id = $id;
		$this->legacy_id = $legacy_id;
	}

	/**
	 * Returns everything of this type.
	 * @return array
	 */
	abstract static function all();

	/**
	 * Returns a match for the given name or ID, or null.
	 * @return Flattenable
	 */
	static function get($arg)
	{
		if(gettype($arg) == "string")
		{
			$arg = strtolower($arg);
			if(substr($arg, 0, 10) == "minecraft:")
			{
				$arg = substr($arg, 10);
			}
			foreach(static::all() as $thing)
			{
				if($thing->name == $arg)
				{
					return $thing;
				}
			}
		}
		else if(gettype($arg) == "integer")
		{
			foreach(static::all() as $thing)
			{
				if($thing->id == $id)
				{
					return $thing;
				}
			}
		}
		else
		{
			throw new \Phpcraft\Exception("Flattenable::get's argument has to be a string or an integer.");
		}
		return null;
	}
}
