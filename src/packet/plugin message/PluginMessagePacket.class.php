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

	protected function read_(Connection $con)
	{
		$this->data = $con->read_buffer;
		$con->read_buffer = "";
	}

	/**
	 * @param Connection $con
	 * @throws IOException
	 */
	public static function read(Connection $con)
	{
		$ret = new (get_called_class())();
		if($con->protocol_version >= 385)
		{
			$ret->channel = $con->readString();
		}
		else
		{
			$legacy_channel = $con->readString();
			$channel = array_search($legacy_channel, self::channelMap());
			if($channel)
			{
				$ret->channel = $channel;
			}
			else
			{
				trigger_error("Unmapped legacy plugin message channel: ".$legacy_channel);
				$ret->channel = $legacy_channel;
			}
		}
		/** @noinspection PhpUndefinedMethodInspection */
		$ret->read_($con);
	}

	protected function send_(Connection $con)
	{
		$con->writeRaw($this->data);
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
		$this->send_($con);
		$con->send();
	}

	public function __toString()
	{
		return "{".substr(get_called_class(), 9).": \"".$this->channel."\": ".Phpcraft::binaryStringToHex($this->data)."}";
	}
}
