<?php
namespace Phpcraft;
class Server
{
	/**
	 * The stream the server listens for new connections on.
	 * @var resource $stream
	 */
	public $stream;
	/**
	 * A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 * @var resource $private_key
	 */
	public $private_key;
	/**
	 * A ClientConnection array of all clients that are connected to the server.
	 * @var array $clients
	 */
	public $clients = [];
	/**
	 * The counter used to assign entity IDs.
	 * @var Counter $eidCounter
	 */
	public $eidCounter;
	/**
	 * The function called when a client has entered state 3 (playing) with the ClientConnection as argument.
	 * @see Server::handle()
	 * @var function $join_function
	 */
	public $join_function = null;
	/**
	 * The function called when the server receives a packet from a client in state 3 (playing) unless it's a keep alive response with the ClientConnection, packet name, and packet id as parameters.
	 * @see Server::handle()
	 * @var function $packet_function
	 */
	public $packet_function = null;
	/**
	 * The function called when a client's disconnected from the server with the ClientConnection as argument.
	 * @see Server::handle()
	 * @var function $disconnect_function
	 */
	public $disconnect_function = null;
	/**
	 * The function called when to get the server's response to a list ping request with the ClientConnection as argument.
	 * See Phpcraft::getServerStatus for an example of all the data a server may respond with (excluding "ping").
	 * @see Server::accept()
	 * @see Server::handle()
	 * @var function $list_ping_function
	 */
	public $list_ping_function = null;

	/**
	 * The constructor.
	 * @param resource $stream A stream created by stream_socket_server.
	 * @param resource $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 */
	function __construct($stream = null, $private_key = null)
	{
		if($stream)
		{
			stream_set_blocking($stream, false);
			$this->stream = $stream;
		}
		$this->private_key = $private_key;
		$this->eidCounter = new \Phpcraft\Counter();
		$this->list_ping_function = function($con)
		{
			$players = [];
			foreach($this->clients as $client)
			{
				if($client->state == 3)
				{
					array_push($players, [
						"name" => $client->username,
						"id" => $client->uuid->toString(true)
					]);
				}
			}
			$versions = \Phpcraft\Phpcraft::getSupportedMinecraftVersions();
			return [
				"version" => [
					"name" => "Phpcraft ".$versions[count($versions) - 1]." - ".$versions[0],
					"protocol" => (\Phpcraft\Phpcraft::isProtocolVersionSupported($con->protocol_version) ? $con->protocol_version : \Phpcraft\Phpcraft::getSupportedProtocolVersions()[0])
				],
				"players" => [
					"online" => count($players),
					"max" => count($players) + 1,
					"sample" => $players
				],
				"description" => [
					"text" => "A \\Phpcraft\\Server"
				]
			];
		};
	}

	/**
	 * Returns whether the server socket is open or not.
	 * @return boolean
	 */
	function isOpen()
	{
		return $this->stream != null && @feof($this->stream) === false;
	}

	/**
	 * Accepts new clients and processes each client's first packet.
	 * @return Server $this
	 */
	function accept()
	{
		while(($stream = @stream_socket_accept($this->stream, 0)) !== false)
		{
			try
			{
				$con = new \Phpcraft\ClientConnection($stream);
				switch($con->handleInitialPacket())
				{
					case 1:
					if($con->state == 1)
					{
						$con->disconnect_after = microtime(true) + 10;
					}
					array_push($this->clients, $con);
					break;

					case 2: // Legacy List Ping
					$json = ($this->list_ping_function)($con);
					if(!isset($json["players"]))
					{
						$json["players"] = [];
					}
					if(!isset($json["players"]["online"]))
					{
						$json["players"]["online"] = 0;
					}
					if(!isset($json["players"]["max"]))
					{
						$json["players"]["max"] = 0;
					}
					$data = "§1\x00127\x00".@$json["version"]["name"]."\x00".\Phpcraft\Phpcraft::chatToText(@$json["description"], 2)."\x00".$json["players"]["online"]."\x00".$json["players"]["max"];
					$con->writeByte(0xFF);
					$con->writeShort(strlen($data) - 1);
					$con->writeRaw(mb_convert_encoding($data, "utf-16be"));
					$con->send(true);
					$con->close();
				}
			}
			catch(Exception $ignored)
			{
				$con->close();
			}
		}
		return $this;
	}

	/**
	 * Deals with all connected clients.
	 * This includes responding to status requests, dealing with keep alive packets, and closing dead connections.
	 * This does not include implementing an entire server; that is what the packet_function is for.
	 * @return Server $this
	 */
	function handle()
	{
		foreach($this->clients as $i => $con)
		{
			if($con->isOpen())
			{
				try
				{
					while(($packet_id = $con->readPacket(0)) !== false)
					{
						if($con->state == 3) // Playing
						{
							$packet_name = \Phpcraft\Packet::serverboundPacketIdToName($packet_id, $con->protocol_version);
							if($packet_name == "keep_alive_response")
							{
								$con->next_heartbeat = microtime(true) + 15;
								$con->disconnect_after = 0;
							}
							else if($this->packet_function)
							{
								($this->packet_function)($con, $packet_name, $packet_id);
							}
						}
						else if($con->state == 2) // Login
						{
							if($packet_id == 0x00) // Login Start
							{
								$con->username = $con->readString();
								if(\Phpcraft\Phpcraft::validateName($con->username))
								{
									if($this->private_key)
									{
										$con->sendEncryptionRequest($this->private_key);
									}
									else
									{
										$con->finishLogin(\Phpcraft\Uuid::v5("OfflinePlayer:".$con->username), $this->eidCounter);
										if($this->join_function)
										{
											($this->join_function)($con);
										}
										$con->next_heartbeat = microtime(true) + 15;
									}
								}
								else
								{
									$con->disconnect_after = microtime(true);
									break;
								}
							}
							else if($packet_id == 0x01 && isset($con->username)) // Encryption Response
							{
								if($json = $con->handleEncryptionResponse($this->private_key))
								{
									$con->finishLogin(\Phpcraft\Uuid::fromString($json["id"]), $this->eidCounter);
									if($this->join_function)
									{
										($this->join_function)($con);
									}
									$con->next_heartbeat = microtime(true) + 15;
								}
							}
							else
							{
								$con->disconnect_after = microtime(true);
								break;
							}
						}
						else // Can only be 1; Status
						{
							if($packet_id == 0x00)
							{
								$con->writeVarInt(0x00);
								$con->writeString(json_encode(($this->list_ping_function)($con)));
								$con->send();
							}
							else if($packet_id == 0x01)
							{
								$con->writeVarInt(0x01);
								$con->writeLong($con->readLong());
								$con->send();
								$con->disconnect_after = microtime(true);
								break;
							}
						}
					}
				}
				catch(Exception $e)
				{
					if($con->username)
					{
						echo "Disconnected ".$con->username.": ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString()."\n";
					}
					$con->disconnect(get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
				}
				if($con->disconnect_after != 0 && $con->disconnect_after <= microtime(true))
				{
					$con->close();
				}
				else if($con->next_heartbeat != 0 && $con->next_heartbeat <= microtime(true))
				{
					(new \Phpcraft\KeepAliveRequestPacket(time()))->send($con);
					$con->next_heartbeat = 0;
					$con->disconnect_after = microtime(true) + 30;
				}
			}
			if(!$con->isOpen())
			{
				if($this->disconnect_function)
				{
					($this->disconnect_function)($con);
				}
				unset($this->clients[$i]);
			}
		}
		return $this;
	}

	/**
	 * Closes all client connections and the server socket.
	 * @param array $reason The reason for closing the server; chat object.
	 */
	function close($reason = [])
	{
		fclose($this->stream);
		foreach($this->clients as $client)
		{
			$client->disconnect($reason);
		}
	}
}
