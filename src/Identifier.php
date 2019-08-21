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

	protected function __construct(string $name, int $since_protocol_version)
	{
		$this->name = $name;
		$this->since_protocol_version = $since_protocol_version;
	}

	/**
	 * Returns an Identifier by its name or null if not found.
	 *
	 * @param string $name
	 * @return self|null
	 */
	static function get(string $name): self
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
	 * Returns everything of this type.
	 *
	 * @return self[]
	 */
	abstract static function all();

	/**
	 * Returns an Identifier by its ID in the given protocol version or null if not found.
	 *
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return self
	 */
	static function getById(int $id, int $protocol_version): self
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

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer
	 */
	abstract function getId(int $protocol_version): int;
}
