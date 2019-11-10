<?php
namespace Phpcraft\Packet\PluginMessage;
use Phpcraft\
{Connection, Exception\IOException, Packet\Packet, Phpcraft};
abstract class PluginMessagePacket extends Packet
{
	const CHANNEL_REGISTER = "minecraft:register";
	const CHANNEL_UNREGISTER = "minecraft:unregister";
	const CHANNEL_BRAND = "minecraft:brand";
	const CHANNEL_BUNGEECORD = "bungeecord:main";
	/**
	 * The name of the plugin message's channel.
	 *
	 * @var string $channel
	 */
	public $channel;
	/**
	 * The data of the plugin message; binary string, as it could be anything.
	 *
	 * @var string $data
	 */
	public $data;
	private $packet_name;

	protected function __construct(string $packet_name, string $channel = "", string $data = "")
	{
		$this->packet_name = $packet_name;
		$this->channel = $channel;
		$this->data = $data;
	}

	/**
	 * @param Connection $con
	 * @return string
	 * @throws IOException
	 */
	protected static function readChannel(Connection $con): string
	{
		if($con->protocol_version >= 385)
		{
			return $con->readString();
		}
		$legacy_channel = $con->readString();
		$channel = array_search($legacy_channel, self::channelMap());
		if($channel)
		{
			return $channel;
		}
		trigger_error("Unmapped legacy plugin message channel: ".$legacy_channel);
		return $legacy_channel;
	}

	private static function channelMap(): array
	{
		return [
			"minecraft:register" => "REGISTER",
			"minecraft:unregister" => "UNREGISTER",
			"minecraft:brand" => "MC|Brand",
			"bungeecord:main" => "BungeeCord"
		];
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		$con->startPacket($this->packet_name);
		if($con->protocol_version >= 385)
		{
			$con->writeString($this->channel);
		}
		else
		{
			$con->writeString(@self::channelMap()[$this->channel] ?? $this->channel);
		}
		$this->send_($con);
		$con->send();
	}

	protected function send_(Connection $con)
	{
		$con->writeRaw($this->data);
	}

	function __toString()
	{
		return "{".substr(get_called_class(), 30).": \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
