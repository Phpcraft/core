<?php
namespace Phpcraft\World;
/**
 * A ChunkGenerator that always "generates" the same chunk, as given in the constructor.
 *
 * @since 0.5
 */
class StaticChunkGenerator extends ChunkGenerator
{
	/**
	 * @var Chunk $chunk
	 */
	public $chunk;

	function __construct(World $world, Chunk $chunk)
	{
		parent::__construct($world);
		$chunk->world = $world;
		$this->chunk = $chunk;
	}

	function generate(int $x, int $z): Chunk
	{
		return $this->chunk->copy($x, $z);
	}
}
