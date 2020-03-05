<?php
namespace Phpcraft\Packet;
use GMP;
use Phpcraft\
{Connection, Exception\IOException};
/**
 * Server-to-client.
 *
 * @since 0.5.9
 */
class UnloadChunkPacket extends Packet
{
	/**
	 * @var GMP $x
	 */
	public $x;
	/**
	 * @var GMP $z
	 */
	public $z;

	function __construct($x, $z)
	{
		$this->x = gmp_init($x);
		$this->z = gmp_init($z);
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return UnloadChunkPacket
	 * @throws IOException
	 */
	static function read(Connection $con): UnloadChunkPacket
	{
		return new UnloadChunkPacket($con->readInt(), $con->readInt());
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
		if($con->protocol_version > 47)
		{
			$con->startPacket("unload_chunk");
			$con->writeInt($this->x);
			$con->writeInt($this->z);
		}
		else
		{
			$con->startPacket("chunk_data");
			$con->writeInt($this->x);
			$con->writeInt($this->z);
			$con->writeBoolean(true);
			$con->writeUnsignedShort(0);
			$con->writeVarInt(0);
		}
		$con->send();
	}

	function __toString()
	{
		return "{UnloadChunkPacket: Chunk {$this->x}, {$this->z}}";
	}
}
