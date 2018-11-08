<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/ServerConnection.class.php";
/** A client-to-server connection with the intention of getting the server's status. */
class ServerStatusConnection extends ServerConnection
{
	/**
	 * The constructor.
	 * After this, you should call ServerStatusConnection::getStatus().
	 * @param string $server_name
	 * @param integer $server_port
	 */
	function __construct($server_name, $server_port = 25565)
	{
		parent::__construct($server_name, $server_port, 1);
	}

	/**
	 * Returns the server list ping as multi-dimensional array with the addition of the "ping" value which is in seconds and closes the connection.
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
	 *         "id" => "4566e69f-c907-48ee-8d71-d7ba5aa00d20"
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
	 * @return array
	 */
	function getStatus()
	{
		// TODO: Legacy list ping
		$start = microtime(true);
		$this->writeVarInt(0x00);
		$this->send();
		if($this->readPacket() != 0x00)
		{
			throw new \Phpcraft\Exception("Invalid response to status request: {$id} ".bin2hex($this->read_buffer)."\n");
		}
		$json = json_decode($this->readString(), true);
		$json["ping"] = microtime(true) - $start;
		$this->close();
		return $json;
	}
}
