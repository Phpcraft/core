<?php
namespace Phpcraft;
/**
 * The class for the IDs of packets sent to the server.
 */
class ServerboundPacket extends PacketId
{
	private static $all_cache;

	private static function nameMap()
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
	 * @copydoc Identifier::all
	 */
	static function all()
	{
		if(!self::$all_cache)
		{
			self::$all_cache = self::_all("toServer", self::nameMap(), function($name, $pv)
			{
				return new ServerboundPacket($name, $pv);
			});
		}
		return self::$all_cache;
	}

	/**
	 * @copydoc Identifier::getId
	 */
	function getId($protocol_version)
	{
		return $protocol_version >= $this->since_protocol_version ? $this->_getId($protocol_version, "toServer", self::nameMap()) : null;
	}

	/**
	 * @copydoc PacketId::init
	 */
	function init(Connection $con)
	{
		switch($this->name)
		{
			case "keep_alive_response":
			return KeepAliveResponsePacket::read($con);

			case "serverbound_plugin_message":
			return ServerboundPluginMessagePacket::read($con);
		}
		return null;
	}
}
