<?php
namespace Phpcraft;
abstract class Identifier
{
	protected static $all_cache;
	/**
	 * The name of this Identifier.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * The protocol version at which this Identifier was introduced.
	 *
	 * @var int $since_protocol_version
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
	 * @return static|null
	 */
	static function get(string $name)
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		if(static::$all_cache === null)
		{
			static::populateAllCache();
		}
		return @static::$all_cache[$name];
	}

	abstract protected static function populateAllCache(): void;

	/**
	 * Returns an Identifier by its ID in the given protocol version or null if not found.
	 *
	 * @param int $id
	 * @param int $protocol_version
	 * @return static|null
	 */
	static function getById(int $id, int $protocol_version): ?Identifier
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
	 * Returns everything of this type.
	 *
	 * @return array<static>
	 */
	static function all(): array
	{
		if(static::$all_cache === null)
		{
			static::populateAllCache();
		}
		return static::$all_cache;
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	abstract function getId(int $protocol_version): ?int;
}
