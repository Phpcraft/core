<?php
namespace Phpcraft;
abstract class Identifier
{
	/**
	 * The name of this Identifier.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * The protocol version at which this Identifier was introduced.
	 *
	 * @var integer $since_protocol_version
	 */
	public $since_protocol_version;

	/**
	 * Returns everything of this type.
	 *
	 * @return Identifier[]
	 */
	abstract public static function all();

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer
	 */
	abstract public function getId(int $protocol_version);

	/**
	 * Returns an Identifier by its name or null if not found.
	 *
	 * @param string $name
	 * @return static
	 */
	public static function get(string $name)
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		foreach(static::all() as $thing)
		{
			if($thing->name == $name)
			{
				return $thing;
			}
		}
		return null;
	}

	/**
	 * Returns an Identifier by its ID in the given protocol version or null if not found.
	 *
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return static
	 */
	public static function getById(int $id, int $protocol_version)
	{
		foreach(static::all() as $thing)
		{
			if($thing->getId($protocol_version) == $id)
			{
				return $thing;
			}
		}
		return null;
	}
}
