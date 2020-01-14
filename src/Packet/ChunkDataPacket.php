<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, World\Chunk, World\ChunkSection};
/**
 * @since 0.5.1
 */
class ChunkDataPacket extends Packet
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
	 * @todo Implement read() method.
	 */
	static function read(Connection $con): ChunkDataPacket
	{
	}

	function send(Connection $con): void
	{
		$con->startPacket("chunk_data");
		$con->writeInt($this->x);
		$con->writeInt($this->z);
		$con->writeBoolean($this->is_new_chunk);
		$this->chunk->write($con);
		$con->send();
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
