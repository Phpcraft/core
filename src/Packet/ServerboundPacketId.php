<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Packet\PluginMessage\ServerboundPluginMessagePacket, PacketId};
/** The class for the IDs of packets sent to the server. */
class ServerboundPacketId extends PacketId
{
	protected static $all_cache;

	/**
	 * Returns a ServerboundPacketId by its name or null if not found.
	 * If you call ServerboundPacketId::get() instead of PacketId::get() you may drop the leading "serverbound_" from applicable packet ids.
	 *
	 * @param string $name
	 * @return ServerboundPacketId|null
	 */
	static function get(string $name)
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		if(self::$all_cache === null)
		{
			self::populateAllCache();
		}
		return self::$all_cache[$name] ?? @self::$all_cache["serverbound_".$name];
	}

	static protected function populateAllCache()
	{
		self::populateAllCache_("toServer");
	}

	protected static function nameMap(): array
	{
		return [
			"position_look" => "position_and_look",
			"flying" => "no_movement",
			"settings" => "client_settings",
			"keep_alive" => "keep_alive_response",
			"abilities" => "serverbound_abilities",
			"chat" => "serverbound_chat_message",
			"custom_payload" => "serverbound_plugin_message",
			"arm_animation" => "serverbound_animation"
		];
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	function getId(int $protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toServer") : null;
	}

	/**
	 * Returns the packet's class or null if the packet does not have a class implementation yet.
	 *
	 * @return string|null
	 */
	function getClass()
	{
		switch($this->name) // Ordered alphabetically
		{
			case "client_settings":
				return ClientSettingsPacket::class;
			case "keep_alive_response":
				return KeepAliveResponsePacket::class;
			case "serverbound_plugin_message":
				return ServerboundPluginMessagePacket::class;
		}
		return null;
	}
}
