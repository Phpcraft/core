<?php
namespace Phpcraft;
abstract class PacketId extends Identifier
{
	private static function versions()
	{
		return [
			383 => "1.13",
			336 => "1.12.1",
			328 => "1.12",
			314 => "1.11",
			110 => "1.9.4",
			107 => "1.9",
			47 => "1.8"
		];
	}

	protected static function _all($key, $name_map, $func)
	{
		$packets = [];
		foreach(array_reverse(self::versions(), true) as $pv => $v)
		{
			foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/timmyrs/minecraft-data/master/data/pc/{$v}/protocol.json")["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"] as $name)
			{
				if(isset($name_map[$name]))
				{
					$name = $name_map[$name];
				}
				if(!isset($packets[$name]))
				{
					$packets[$name] = ($func)($name, $pv);
				}
			}
		}
		return array_values($packets);
	}

	/**
	 * @copydoc Identifier::all
	 */
	static function all()
	{
		return array_merge(ClientboundPacket::all(), ServerboundPacket::all());
	}

	protected function __construct($name, $since_protocol_version)
	{
		$this->name = $name;
		$this->since_protocol_version = $since_protocol_version;
	}

	protected function _getId($protocol_version, $key, $name_map)
	{
		foreach(self::versions() as $pv => $v)
		{
			if($protocol_version >= $pv)
			{
				foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/timmyrs/minecraft-data/master/data/pc/{$v}/protocol.json")["play"][$key]["types"]["packet"][1][0]["type"][1]["mappings"] as $id => $name)
				{
					if((isset($name_map[$name]) ? $name_map[$name] : $name) == $this->name)
					{
						return hexdec(substr($id, 2));
					}
				}
			}
		}
	}

	/**
	 * Initialises this packet's class by reading its payload from the given Connection.
	 * Returns null if the packet does not have a class implementation yet.
	 * @return Packet
	 */
	abstract function init(Connection $con);
}
