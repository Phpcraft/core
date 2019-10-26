<?php
namespace Phpcraft;
use Phpcraft\Exception\IOException;
class LanInterface
{
	const MSG_REGEX = '/^\[MOTD\]([^\[\]]+)\[\/MOTD\]\[AD\]([0-9]{4,5})\[\/AD\]$/';
	public $servers = [];
	private $socket;

	/**
	 * Constructs a LanInterface to -&gt;discover LAN servers.
	 * Unlike LanInterface::announce, this requires the "sockets" extension.
	 *
	 * @throws IOException
	 */
	function __construct()
	{
		$this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if(!$this->socket)
		{
			throw new IOException("Failed to open socket");
		}
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		if(!socket_bind($this->socket, "0.0.0.0", 4445))
		{
			throw new IOException("Failed to bind socket");
		}
		socket_set_option($this->socket, IPPROTO_IP, MCAST_JOIN_GROUP, [
			"group" => "224.0.2.60",
			"interface" => 0
		]);
		socket_set_nonblock($this->socket);
	}

	/**
	 * Announces a LAN server.
	 * Minecraft does this every 1.5 seconds and once a host:port has been sent, it is added to the server list until the server list is refreshed, and can't be updated.
	 *
	 * @param string $motd Supports § format for colour.
	 * @todo more research on § format looking weird in older versions
	 * @param int|string $port Although this is supposed to be an integer, Minecraft accepts and displays any string but connects to :25565 if this is not a valid port. Do with that as you wish.
	 * @throws IOException
	 */
	static function announce(string $motd, $port)
	{
		$stream = stream_socket_client("udp://224.0.2.60:4445", $errno, $errstr, 0, STREAM_CLIENT_CONNECT, stream_context_create([
			"socket" => [
				"so_reuseport" => true,
				"so_broadcast" => true
			]
		]));
		if(!$stream)
		{
			throw new IOException("Failed to open stream: $errstr ($errno)");
		}
		stream_socket_sendto($stream, "[MOTD]{$motd}[/MOTD][AD]{$port}[/AD]");
		fclose($stream);
	}

	/**
	 * Checks for worlds/servers on the local network and updates $this-&gt;servers.
	 * Unlike Minecraft, Phpcraft dynamically updates the server list including MOTD changes and requires a server to be announced regularly, otherwise it will be removed.
	 * This function only needs to be called every 1-2 seconds (optimally every 1.5 seconds).
	 * If you want all servers at the time of execution, initiate a LanInterface instance, wait 1.5 to 2 seconds, then call discover, and finally access its -&gtservers.
	 */
	function discover()
	{
		do
		{
			if(!socket_recvfrom($this->socket, $msg, 1024, 0, $from, $port))
			{
				break;
			}
			if(preg_match(self::MSG_REGEX, $msg, $matches) === 1)
			{
				$this->servers[$from.":".$matches[2]] = [
					"motd" => $matches[1],
					"host" => $from,
					"port" => $matches[2],
					"last_announcement" => time()
				];
			}
		}
		while(true);
		$last_valid = time() - 3;
		foreach($this->servers as $addr => $server)
		{
			if($server["last_announcement"] < $last_valid)
			{
				unset($this->servers[$addr]);
			}
		}
	}
}
