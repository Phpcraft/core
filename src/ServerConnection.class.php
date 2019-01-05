<?php
namespace Phpcraft;
require_once __DIR__."/validate.php";
require_once __DIR__."/Connection.class.php";
/** A client-to-server connection. */
class ServerConnection extends Connection
{
	/**
	 * The constructor.
	 * @param string $server_name
	 * @param integer $server_port
	 * @param integer $next_state 1 stands for status and 2 for login to play. Use 0 to disable automatic handshake.
	 * @param integer $protocol_version
	 */
	function __construct($server_name, $server_port, $next_state, $protocol_version = 404)
	{
		if(!($stream = fsockopen($server_name, $server_port, $errno, $errstr, 10)))
		{
			throw new \Phpcraft\Exception($errstr);
		}
		stream_set_blocking($stream, false); 
		parent::__construct($protocol_version, $stream);
		if($next_state > 0)
		{
			$this->writeVarInt(0x00);
			$this->writeVarInt($protocol_version);
			$this->writeString($server_name);
			$this->writeShort($server_port);
			$this->writeVarInt($this->state = $next_state);
			$this->send();
		}
	}
}