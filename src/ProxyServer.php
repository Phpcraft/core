<?php
namespace Phpcraft;
use Exception;
use Phpcraft\
{Command\Command, Enum\Dimension, Enum\Gamemode, Event\ProxyClientPacketEvent, Event\ProxyConnectEvent, Event\ProxyLeaveEvent, Event\ProxyServerPacketEvent, Event\ProxyTickEvent, Event\ServerJoinEvent, Event\ServerTickEvent, Exception\IOException, Packet\ClientboundChatMessagePacket, Packet\ClientboundPacketId, Packet\EntityPacket, Packet\JoinGamePacket, Packet\KeepAliveRequestPacket, Packet\PluginMessage\ClientboundBrandPluginMessagePacket, Packet\RespawnPacket, Packet\ServerboundPacketId};
/**
 * @since 0.3
 */
class ProxyServer extends IntegratedServer
{
	function __construct(?string $name = null, array $custom_config_defaults = [], ?UserInterface $ui = null, $private_key = null)
	{
		parent::__construct($name, $custom_config_defaults, $ui, $private_key);
		$this->tick_loop->remove();
		$this->open_condition->add(function(bool $lagging)
		{
			PluginManager::fire(new ProxyTickEvent($this, $lagging));
			PluginManager::fire(new ServerTickEvent($this, $lagging));
		}, 0.05);
		$this->open_condition->add(function()
		{
			foreach($this->clients as $con)
			{
				/**
				 * @var ClientConnection $con
				 */
				try
				{
					if(@$con->downstream !== null && $packet_id = $con->downstream->readPacket(0))
					{
						$packetId = ClientboundPacketId::getById($packet_id, $con->downstream->protocol_version);
						if(PluginManager::fire(new ProxyClientPacketEvent($this, $con, $packetId)))
						{
							continue;
						}
						if(in_array($packetId->name, [
							"entity_animation",
							"entity_effect",
							"entity_metadata",
							"entity_velocity"
						]))
						{
							$packet = $packetId->getInstance($con->downstream);
							assert($packet instanceof EntityPacket);
							$packet->replaceEntity($con->downstream_eid, $con->eid);
							$packet->send($con);
						}
						else if($packetId->name == "keep_alive_request")
						{
							KeepAliveRequestPacket::read($con->downstream)
												  ->getResponse()
												  ->send($con->downstream);
						}
						else if($packetId->name == "disconnect")
						{
							(new ClientboundChatMessagePacket($con->downstream->readChat()))->send($con);
							$con->downstream->close();
							$con->downstream = null;
							break;
						}
						else if($packetId->name == "join_game")
						{
							$packet = JoinGamePacket::read($con->downstream);
							$con->downstream_eid = $packet->eid;
							if($con->dimension === null)
							{
								$packet->eid = $con->eid;
								$con->dimension = $packet->dimension;
								$con->gamemode = $packet->gamemode;
								$packet->send($con);
							}
							else
							{
								if($packet->dimension == $con->dimension)
								{
									$respawn_packet = new RespawnPacket();
									$respawn_packet->dimension = $packet->dimension == Dimension::OVERWORLD ? Dimension::END : Dimension::OVERWORLD;
									$respawn_packet->send($con);
								}
								$respawn_packet = new RespawnPacket();
								$respawn_packet->dimension = $con->dimension = $packet->dimension;
								$respawn_packet->gamemode = $con->gamemode = $packet->gamemode;
								$respawn_packet->send($con);
							}
						}
						else if($packetId->name == "teleport")
						{
							$read_buffer_offsset = $con->downstream->read_buffer_offset;
							$con->downstream->startPacket("teleport_confirm");
							$con->downstream->writeVarInt($con->downstream->ignoreBytes(33)
																		  ->readVarInt());
							$con->downstream->send();
							$con->downstream->read_buffer_offset = $read_buffer_offsset;
							$con->startPacket($packetId);
							$con->write_buffer .= $con->downstream->getRemainingData();
							$con->send();
						}
						else if($packetId->name == "set_compression")
						{
							$con->downstream->compression_threshold = $con->downstream->readVarInt();
						}
						else if($con->convert_packets && ($packet = $packetId->getInstance($con->downstream)))
						{
							$packet->send($con);
						}
						else if($packetId->since_protocol_version <= $con->protocol_version)
						{
							$con->startPacket($packetId);
							$con->write_buffer .= $con->downstream->getRemainingData();
							$con->send();
						}
					}
				}
				catch(Exception $e)
				{
					$trace = $e->getMessage();
					foreach($e->getTrace() as $item)
					{
						$trace .= "\n".$item["function"];
						if(array_key_exists("file", $item))
						{
							$trace .= " in ".basename($item["file"]);
							if(array_key_exists("line", $item))
							{
								$trace .= ":".$item["line"];
							}
						}
					}
					$con->disconnect("[".$this->name."] ".$trace);
				}
			}
		}, 0.001);
		$integrated_packet_function = $this->packet_function;
		$this->packet_function = function(ClientConnection $con, ServerboundPacketId $packetId) use (&$integrated_packet_function)
		{
			if($con->downstream === null)
			{
				$integrated_packet_function($con, $packetId);
				return;
			}
			if(PluginManager::fire(new ProxyServerPacketEvent($this, $con, $packetId)))
			{
				return;
			}
			if($packetId->name == "serverbound_chat_message")
			{
				$msg = $con->readString($con->protocol_version < 314 ? 100 : 256);
				if(!Command::handleMessage($con, $msg))
				{
					$con->downstream->startPacket("serverbound_chat_message");
					$con->downstream->writeString($msg);
					$con->downstream->send();
				}
			}
			else if($packetId->name == "entity_action")
			{
				$con->downstream->startPacket($packetId);
				$eid = $con->readVarInt();
				$con->downstream->writeVarInt(gmp_cmp($eid, $con->eid) == 0 ? $con->downstream_eid : $eid);
				$con->downstream->write_buffer .= $con->getRemainingData();
				$con->downstream->send();
			}
			else if($con->convert_packets && ($packet = $packetId->getInstance($con)))
			{
				$packet->send($con->downstream);
			}
			else if($packetId->since_protocol_version <= $con->downstream->protocol_version)
			{
				$con->downstream->startPacket($packetId);
				$con->downstream->write_buffer .= $con->getRemainingData();
				$con->downstream->send();
			}
		};
		$integrated_disconnect_function = $this->disconnect_function;
		$this->disconnect_function = function(ClientConnection $con) use (&$integrated_disconnect_function)
		{
			if($con->downstream === null)
			{
				$integrated_disconnect_function($con);
			}
			else
			{
				$con->downstream->close();
			}
			PluginManager::fire(new ProxyLeaveEvent($this, $con));
		};
	}

	static function getDefaultName(): string
	{
		return "Phpcraft Proxy Server";
	}

	/**
	 * Returns all players that are not connected to a downstream server.
	 *
	 * @return ClientConnection[]
	 */
	function getPlayers(): array
	{
		$clients = [];
		foreach($this->clients as $client)
		{
			if($client->state == Connection::STATE_PLAY && $client->downstream === null)
			{
				array_push($clients, $client);
			}
		}
		return $clients;
	}

	/**
	 * Attempts to connect the given client to the given server.
	 *
	 * @param ClientConnection $con
	 * @param string $address
	 * @param Account|null $account
	 * @param array $join_specs
	 * @return string|null Error message or null on success.
	 * @throws IOException
	 */
	function connectDownstream(ClientConnection $con, string $address, Account $account = null, array $join_specs = []): ?string
	{
		self::preconnectCleanup($con);
		$address = Phpcraft::resolve($address);
		$arr = explode(":", $address);
		if(count($arr) != 2)
		{
			return "Server address resolved to $address";
		}
		$arr[1] = intval($arr[1]);
		$stream = @fsockopen($arr[0], $arr[1], $errno, $errstr, 3);
		if(!$stream)
		{
			return $errstr;
		}
		$con->downstream = new ServerConnection($stream, $con->protocol_version);
		$con->downstream->sendHandshake($arr[0], $arr[1], Connection::STATE_STATUS);
		$con->downstream->writeVarInt(0x00); // Status Request
		$con->downstream->send();
		$packet_id = $con->downstream->readPacket(0.3);
		if($packet_id !== 0x00)
		{
			$con->downstream->close();
			return "Server answered status request with packet id ".$packet_id;
		}
		$json = json_decode($con->downstream->readString(), true);
		$con->downstream->close();
		if(empty($json) || empty($json["version"]) || empty($json["version"]["protocol"]))
		{
			return "Invalid status response: ".json_encode($json);
		}
		if($con->convert_packets = ($json["version"]["protocol"] != $con->protocol_version))
		{
			if(!Versions::protocolSupported($json["version"]["protocol"]))
			{
				return "Server doesn't support ".Versions::protocolToRange($con->protocol_version).", suggests using ".Versions::protocolToRange($json["version"]["protocol"]).". Phpcraft will probably not be able to convert packets reliably.";
			}
		}
		$stream = @fsockopen($arr[0], $arr[1], $errno, $errstr, 3);
		if(!$stream)
		{
			return $errstr;
		}
		$con->downstream = new ServerConnection($stream, $con->convert_packets ? $json["version"]["protocol"] : $con->protocol_version);
		if($account === null)
		{
			$join_specs = [$con->getRemoteAddress()];
			if($this->isOnlineMode())
			{
				array_push($join_specs, $con->uuid);
			}
			$account = new Account($con->username);
		}
		$con->downstream->sendHandshake($arr[0], $arr[1], Connection::STATE_LOGIN, $join_specs);
		if($error = $con->downstream->login($account))
		{
			return $error;
		}
		PluginManager::fire(new ProxyConnectEvent($this, $con, $address));
		return null;
	}

	/**
	 * @param ClientConnection $con
	 * @return void
	 * @throws IOException
	 */
	private static function preconnectCleanup(ClientConnection $con): void
	{
		$con->unloadChunks();
		$con->downstream_eid = $con->eid;
		if($con->downstream !== null)
		{
			$con->downstream->close();
			$con->downstream = null;
		}
	}

	/**
	 * "Connects" the given client to the underlying integrated server.
	 *
	 * @param ClientConnection $con
	 * @throws IOException
	 */
	function connectToIntegratedServer(ClientConnection $con): void
	{
		self::preconnectCleanup($con);
		if(@$con->dimension === null)
		{
			$packet = new JoinGamePacket($con->eid);
			$packet->gamemode = $con->gamemode = Gamemode::CREATIVE;
			$con->dimension = $packet->dimension;
			$packet->render_distance = 32;
			$packet->send($con);
		}
		else
		{
			if(@$con->dimension == Dimension::OVERWORLD)
			{
				$packet = new RespawnPacket();
				$packet->dimension = Dimension::END;
				$packet->send($con);
			}
			$packet = new RespawnPacket();
			$packet->dimension = $con->dimension = Dimension::OVERWORLD;
			$packet->gamemode = $con->gamemode = Gamemode::CREATIVE;
			$packet->send($con);
			$con->teleport(new Point3D(0, 16, 0));
		}
		(new ClientboundBrandPluginMessagePacket($this->name))->send($con);
		$con->startPacket("spawn_position");
		$con->writePosition($con->pos = $this->spawn_position);
		$con->send();
		$con->teleport($con->pos);
		PluginManager::fire(new ServerJoinEvent($this, $con));
	}
}
