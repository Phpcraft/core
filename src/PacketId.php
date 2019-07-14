<?php
namespace Phpcraft;
use Phpcraft\Packet\
{ClientboundPacket, Packet, ServerboundPacket};
abstract class PacketId extends Identifier
{
	protected function __construct(string $name, int $since_protocol_version)
	{
		$this->name = $name;
		$this->since_protocol_version = $since_protocol_version;
	}

	/**
	 * Returns every ClientboundPacket and ServerboundPacket.
	 *
	 * @return PacketId[]
	 */
	public static function all()
	{
		return array_merge(ClientboundPacket::all(), ServerboundPacket::all());
	}

	protected static function _all(string $key, array $name_map, callable $func)
	{
		$packets = [];
		foreach(array_reverse(self::versions(), true) as $pv => $v)
		{
			foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/{$v}/protocol.json")["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"] as $name)
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

	private static function versions()
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
	 * @return Packet
	 */
	abstract public function init(Connection $con);

	protected function _getId(int $protocol_version, string $key, array $name_map)
	{
		foreach(self::versions() as $pv => $v)
		{
			if($protocol_version >= $pv)
			{
				foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/{$v}/protocol.json")["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"] as $id => $name)
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
