<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Exception\IOException};
/** Sent by the server to the client to make sure it's still connected. */
class KeepAliveRequestPacket extends Packet
{
	/**
	 * The identifier of this keep alive packet.
	 *
	 * @var GMP $keepAliveId
	 */
	public $keepAliveId;

	/**
	 * @param GMP|string|integer $keepAliveId The identifier of this keep alive packet. Defaults to current time.
	 */
	function __construct($keepAliveId = null)
	{
		if($keepAliveId === null)
		{
			$keepAliveId = gmp_init(time());
		}
		else if(!$keepAliveId instanceof GMP)
		{
			$keepAliveId = gmp_init($keepAliveId);
		}
		$this->keepAliveId = $keepAliveId;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return KeepAliveRequestPacket
	 * @throws IOException
	 */
	static function read(Connection $con): KeepAliveRequestPacket
	{
		return new KeepAliveRequestPacket($con->protocol_version >= 339 ? $con->readLong() : $con->readVarInt());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @return void
	 * @throws IOException
	 */
	function send(Connection $con): void
	{
		$con->startPacket("keep_alive_request");
		if($con->protocol_version >= 339)
		{
			$con->writeLong($this->keepAliveId);
		}
		else
		{
			$con->writeVarInt($this->keepAliveId);
		}
		$con->send();
	}

	/**
	 * Generates the response packet which the client should send.
	 *
	 * @return KeepAliveResponsePacket
	 */
	function getResponse(): KeepAliveResponsePacket
	{
		return new KeepAliveResponsePacket($this->keepAliveId);
	}

	function __toString()
	{
		return "{Keep Alive Request: ID ".gmp_strval($this->keepAliveId)."}";
	}
}
