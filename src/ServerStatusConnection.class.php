<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/ServerConnection.class.php";
/** A client-to-server connection with the intention of getting the server's status. */
class ServerStatusConnection
{
	private $server_name;
	private $server_port;

	/**
	 * The constructor.
	 * After this, you should call ServerStatusConnection::getStatus().
	 * @param string $server_name
	 * @param integer $server_port
	 */
	function __construct($server_name, $server_port = 25565)
	{
		$this->server_name = $server_name;
		$this->server_port = $server_port;
	}

	/**
	 * Returns the server list ping as multi-dimensional array with the addition of the "ping" value which is in seconds. In an error case, an empty array is returned.
	 * Here's an example:
	 * <pre>[
	 *   "version" => [
	 *     "name" => "1.12.2",
	 *     "protocol" => 340
	 *   ],
	 *   "players" => [
	 *     "max" => 20,
	 *     "online" => 1,
	 *     "sample" => [
	 *       [
	 *         "name" => "timmyRS",
	 *         "id" => "e0603b59-2edc-45f7-acc7-b0cccd6656e1"
	 *       ]
	 *     ]
	 *   ],
	 *   "description" => [
	 *     "text" => "A Minecraft Server"
	 *   ],
	 *   "favicon" => "data:image/png;base64,&lt;data&gt;",
	 *   "ping" => 0.068003177642822
	 * ]</pre>
	 *
	 * Note that a server might not present all of these values, so always check with `isset` first.
	 *
	 * Also, the `description` is a chat object, so you can pass it to Phpcraft::chatToANSIText().
	 * @param float $timeout The amount of seconds to wait for a response with each method.
	 * @param integer $method The method(s) used to get the status. 2 = legacy list ping, 1 = modern list ping, 0 = both.
	 * @return array
	 */
	function getStatus($timeout = 3.000, $method = 0)
	{
		if($method != 2)
		{
			$con = new ServerConnection($this->server_name, $this->server_port, 1);
			$start = microtime(true);
			$con->writeVarInt(0x00);
			$con->send();
			if($con->readPacket($timeout) === 0x00)
			{
				$json = json_decode($con->readString(), true);
				$json["ping"] = microtime(true) - $start;
				$con->close();
				return $json;
			}
			$con->close();
		}
		if($method != 1)
		{
			$con = new ServerConnection($this->server_name, $this->server_port, 0);
			$start = microtime(true);
			$con->writeByte(0xFE);
			$con->writeByte(0x01);
			$con->writeByte(0xFA);
			$con->writeShort(11);
			$con->writeRaw(mb_convert_encoding("MC|PingHost", "utf-16be"));
			$host = mb_convert_encoding($this->server_name, "utf-16be");
			$con->writeShort(strlen($host) + 7);
			$con->writeByte(73); // Protocol Version
			$con->writeShort(strlen($this->server_name));
			$con->writeRaw($host);
			$con->writeInt($this->server_port);
			$con->send(true);
			if($con->readPacket($timeout, true))
			{
				$arr = explode("\x00\x00", substr($con->getReadBuffer(), 9));
				$con->close();
				return [
					"version" => [
						"protocol" => mb_convert_encoding($arr[0], mb_internal_encoding(), "utf-16be"),
						"name" => mb_convert_encoding($arr[1], mb_internal_encoding(), "utf-16be")
					],
					"players" => [
						"max" => intval(mb_convert_encoding($arr[4], mb_internal_encoding(), "utf-16be")),
						"online" => intval(mb_convert_encoding($arr[3], mb_internal_encoding(), "utf-16be"))
					],
					"description" => Phpcraft::textToChat(mb_convert_encoding($arr[2], mb_internal_encoding(), "utf-16be")),
					"ping" => (microtime(true) - $start)
				];
			}
			$con->close();
		}
		return [];
	}
}
