<?php
namespace Phpcraft;
use Phpcraft\Packet\
{ClientboundPacketId, Packet, ServerboundPacketId};
abstract class PacketId extends Identifier
{
	protected static $all_cache;
	private static $mappings = [];

	static protected function populateAllCache()
	{
		self::$all_cache = ClientboundPacketId::all() + ServerboundPacketId::all();
	}

	protected static function populateAllCache_(string $key)
	{
		$name_map = static::nameMap();
		static::$all_cache = [];
		foreach(array_reverse(self::versions(), true) as $pv => $v)
		{
			if(!array_key_exists($key.$v, self::$mappings))
			{
				self::$mappings[$key.$v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/protocol.json"), true)["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"];
			}
			foreach(self::$mappings[$key.$v] as $name)
			{
				if(isset($name_map[$name]))
				{
					$name = $name_map[$name];
				}
				if(!isset(static::$all_cache[$name]))
				{
					static::$all_cache[$name] = new static($name, $pv);
				}
			}
		}
	}

	abstract static protected function nameMap(): array;

	private static function versions(): array
	{
		return [
			472 => "1.14.1",
			383 => "1.13",
			336 => "1.12.1",
			328 => "1.12",
			314 => "1.11",
			110 => "1.9.4",
			107 => "1.9",
			47 => "1.8"
		];
	}

	/**
	 * Initialises this packet's class by reading its payload from the given Connection.
	 * Returns null if the packet does not have a class implementation yet.
	 *
	 * @param Connection $con
	 * @return Packet|null
	 * @deprecated Use PacketId::getInstance, instead.
	 */
	function init(Connection $con)
	{
		return $this->getInstance($con);
	}

	/**
	 * Returns the packet's class or null if the packet does not have a class implementation yet.
	 *
	 * @return string|null
	 */
	abstract function getClass();

	/**
	 * Initialises this packet's class, optionally reading its payload from the given Connection.
	 * Returns null if the packet does not have a class implementation yet.
	 *
	 * @param Connection|null $con
	 * @return Packet|null
	 */
	function getInstance(Connection $con = null)
	{
		$class = $this->getClass();
		if($class === null)
		{
			return null;
		}
		return $con === null ? new $class : call_user_func($class."::read", $con);
	}

	protected function _getId(int $protocol_version, string $key)
	{
		$name_map = static::nameMap();
		foreach(self::versions() as $pv => $v)
		{
			if($protocol_version >= $pv)
			{
				if(!array_key_exists($key.$v, self::$mappings))
				{
					self::$mappings[$key.$v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/protocol.json"), true)["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"];
				}
				foreach(self::$mappings[$key.$v] as $id => $name)
				{
					if(($name_map[$name] ?? $name) == $this->name)
					{
						return hexdec(substr($id, 2));
					}
				}
				break;
			}
		}
		return null;
	}
}
