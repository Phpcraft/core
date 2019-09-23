<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Packet\PluginMessage\ServerboundPluginMessagePacket, PacketId};
/** The class for the IDs of packets sent to the server. */
class ServerboundPacket extends PacketId
{
	private static $all_cache;

	/**
	 * Returns every ServerboundPacket.
	 *
	 * @return ServerboundPacket[]
	 */
	static function all(): array
	{
		if(self::$all_cache == null)
		{
			self::$all_cache = self::_all("toServer", self::nameMap(), function(string $name, int $pv)
			{
				return new ServerboundPacket($name, $pv);
			});
		}
		return self::$all_cache;
	}

	private static function nameMap(): array
	{
		return [
			"position_look" => "position_and_look",
			"flying" => "no_movement",
			"settings" => "client_settings",
			"keep_alive" => "keep_alive_response",
			"abilities" => "serverbound_abilities",
			"chat" => "serverbound_chat_message",
			"custom_payload" => "serverbound_plugin_message"
		];
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer|null
	 */
	function getId(int $protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toServer", self::nameMap()) : null;
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
