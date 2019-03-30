<?php
namespace Phpcraft;
abstract class PluginMessagePacket extends Packet
{
	const CHANNEL_REGISTER = "minecraft:register";
	const CHANNEL_UNREGISTER = "minecraft:unregister";
	const CHANNEL_BRAND = "minecraft:brand";
	const CHANNEL_BUNGEECORD = "bungeecord:main";

	private $packet_name;
	/**
	 * The name of the plugin message's channel.
	 * @var string $channel
	 */
	public $channel;
	/**
	 * The data of the plugin message; binary string, as it could be anything.
	 * @var string $data
	 */
	public $data;

	private static function channelMap()
	{
		return [
			"minecraft:register" => "REGISTER",
			"minecraft:unregister" => "UNREGISTER",
			"minecraft:brand" => "MC|Brand",
			"bungeecord:main" => "BungeeCord"
		];
	}

	protected function __construct(string $packet_name, string $channel = "", string $data = "")
	{
		$this->packet_name = $packet_name;
		$this->channel = $channel;
		$this->data = $data;
	}

	/**
	 * @param Connection $con
	 * @throws IOException
	 */
	protected function _read(Connection $con)
	{
		if($con->protocol_version >= 385)
		{
			$this->channel = $con->readString();
		}
		else
		{
			$legacy_channel = $con->readString();
			$channel = array_search($legacy_channel, self::channelMap());
			if($channel)
			{
				$this->channel = $channel;
			}
			else
			{
				trigger_error("Unmapped legacy plugin message channel: ".$legacy_channel);
				$this->channel = $legacy_channel;
			}
		}
		$this->data = $con->read_buffer;
		$con->read_buffer = "";
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws IOException
	 */
	public function send(Connection $con)
	{
		$con->startPacket($this->packet_name);
		if($con->protocol_version >= 385)
		{
			$con->writeString($this->channel);
		}
		else
		{
			$legacy_channel = @self::channelMap()[$this->channel];
			if($legacy_channel)
			{
				$con->writeString($legacy_channel);
			}
			else
			{
				trigger_error("Unmapped plugin message channel: ".$this->channel);
				$con->writeString($this->channel);
			}
		}
		$con->writeRaw($this->data);
		$con->send();
	}
}
