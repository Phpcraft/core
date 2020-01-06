<?php
namespace Phpcraft\Packet;
use Phpcraft\Connection;
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

	function __construct(int $x = 0, int $z = 0, bool $is_new_chunk = true)
	{
		$this->x = $x;
		$this->z = $z;
		$this->is_new_chunk = $is_new_chunk;
	}

	static function read(Connection $con): ChunkDataPacket
	{
		// TODO: Implement read() method.
	}

	function send(Connection $con): void
	{
		// TODO: Implement send() method.
	}

	function __toString()
	{
		// TODO: Implement __toString() method.
	}
}
