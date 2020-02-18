<?php
namespace Phpcraft\Packet;
use Phpcraft\
{BareServer, Connection, Exception\IOException, World\Chunk, World\ChunkSection};
/**
 * @since 0.5.1
 */
class ChunkDataPacket extends Packet implements ServerChangingPacket
{
	/**
	 * @var int $x
	 */
	public $x;
	/**
	 * @var int $z
	 */
	public $z;
	/**
	 * @var bool $is_new_chunk
	 */
	public $is_new_chunk;
	/**
	 * @var Chunk $data
	 */
	public $chunk;

	function __construct(int $x = 0, int $z = 0, bool $is_new_chunk = true, ?Chunk $chunk = null)
	{
		$this->x = $x;
		$this->z = $z;
		$this->is_new_chunk = $is_new_chunk;
		$this->chunk = $chunk;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return static|null
	 * @throws IOException
	 * @since 0.5.5
	 */
	static function read(Connection $con): ChunkDataPacket
	{
		$packet = new ChunkDataPacket();
		$packet->x = $con->readInt();
		$packet->z = $con->readInt();
		$packet->is_new_chunk = $con->readBoolean();
		$packet->chunk = new Chunk($packet->x, $packet->z);
		$packet->chunk->read($con);
		return $packet;
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
		$con->startPacket("chunk_data");
		$con->writeInt($this->x);
		$con->writeInt($this->z);
		$con->writeBoolean($this->is_new_chunk);
		$this->chunk->write($con);
		$con->send();
	}

	/**
	 * @param BareServer $server
	 */
	function apply(BareServer $server): void
	{
		$server->world->chunks[$this->x.":".$this->z] = $this->chunk;
	}

	function __toString()
	{
		$sections = 0;
		for($i = 0; $i < 16; $i++)
		{
			if($this->chunk->getSection($i) instanceof ChunkSection)
			{
				$sections++;
			}
		}
		return "{ChunkDataPacket: ".($this->is_new_chunk ? "New" : "Update")." Chunk {$this->x}, {$this->z} with $sections sections}";
	}
}
