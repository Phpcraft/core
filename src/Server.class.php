<?php
namespace Phpcraft;
require_once __DIR__."/ClientConnection.class.php";
require_once __DIR__."/KeepAliveRequestPacket.class.php";
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
	 * The message of the day (motd) visible in the server list; chat object.
	 * @var string $motd
	 */
	public $motd = ["text" => "A Phpcraft Server"];
	/**
	 * A ClientConnection array of all clients that are connected to the server.
	 * @var array $clients
	 */
	public $clients = [];
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
	 * The constructor.
	 * @param resource $stream A stream created by stream_socket_server.
	 * @param array $motd The message of the day (motd) visible in the server list; chat object.
	 * @param resource $private_key A private key generated using openssl_pkey_new(["private_key_bits" => 1024, "private_key_type" => OPENSSL_KEYTYPE_RSA]) to use for online mode, or null to use offline mode.
	 */
	function __construct($stream = null, $motd = ["text" => "A Phpcraft Server"], $private_key = null)
	{
		if($stream)
		{
			stream_set_blocking($stream, false);
			$this->stream = $stream;
		}
		if($motd)
		{
			$this->motd = $motd;
		}
		$this->private_key = $private_key;
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
			$con = new \Phpcraft\ClientConnection($stream);
			switch($con->handleInitialPacket())
			{
				case 1:
				if($con->getState() == 1)
				{
					$con->disconnect_after = microtime(true) + 10;
				}
				array_push($this->clients, $con);
				break;

				case 2:
				// TODO: Legacy List Ping
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
		$join_function = $this->join_function;
		$packet_function = $this->packet_function;
		$disconnect_function = $this->disconnect_function;
		foreach($this->clients as $i => $con)
		{
			if($con->isOpen())
			{
				try
				{
					while(($packet_id = $con->readPacket(0)) !== false)
					{
						if($con->getState() == 3) // Playing
						{
							$packet_name = \Phpcraft\Packet::serverboundPacketIdToName($packet_id, $con->getProtocolVersion());
							if($packet_name == "keep_alive_response")
							{
								$con->next_heartbeat = microtime(true) + 15;
								$con->disconnect_after = 0;
							}
							else if($packet_function)
							{
								$packet_function($con, $packet_name, $packet_id);
							}
						}
						else if($con->getState() == 2) // Login
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
										$con->finishLogin(\Phpcraft\Phpcraft::generateUUIDv4(true), $con->username);
										if($join_function)
										{
											$join_function($con);
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
								if($json = $con->handleEncryptionResponse($con->username, $this->private_key))
								{
									$con->finishLogin(\Phpcraft\Phpcraft::addHypensToUUID($json["id"]), $con->username);
									if($join_function)
									{
										$join_function($con);
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
								$con->writeString(json_encode([
									"version" => [
										"name" => "\\Phpcraft\\Server",
										"protocol" => (\Phpcraft\Phpcraft::isProtocolVersionSupported($con->getProtocolVersion()) ? $con->getProtocolVersion() : 69)
									],
									"description" => $this->motd
								]));
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
					$con->disconnect(get_class($e).": ".$e->getMessage());
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
				if($disconnect_function)
				{
					$disconnect_function($con);
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
