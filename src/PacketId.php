<?php
namespace Phpcraft;
use Phpcraft\Packet\
{ClientboundPacket, Packet, ServerboundPacket};
abstract class PacketId extends Identifier
{
	private static $mappings = [];

	/**
	 * Returns every ClientboundPacket and ServerboundPacket.
	 *
	 * @return static[]
	 */
	static function all(): array
	{
		return array_merge(ClientboundPacket::all(), ServerboundPacket::all());
	}

	protected static function _all(string $key, array $name_map, callable $func): array
	{
		$packets = [];
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
				if(!isset($packets[$name]))
				{
					$packets[$name] = $func($name, $pv);
				}
			}
		}
		return array_values($packets);
	}

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
	 */
	function init(Connection $con)
	{
		$class = $this->getClass();
		return $class ? call_user_func($class."::read", $con) : null;
	}

	/**
	 * Returns the packet's class or null if the packet does not have a class implementation yet.
	 *
	 * @return string|null
	 */
	abstract function getClass();

	protected function _getId(int $protocol_version, string $key, array $name_map)
	{
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
					if((isset($name_map[$name]) ? $name_map[$name] : $name) == $this->name)
					{
						return hexdec(substr($id, 2));
					}
				}
			}
		}
		return null;
	}
}
