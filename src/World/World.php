<?php
namespace Phpcraft\World;
use Phpcraft\Point3D;
/**
 * @since 0.5
 */
class World
{
	/**
	 * @var ChunkGenerator $chunkGenerator
	 */
	public $chunkGenerator;
	public $chunks = [];
	public $changed_chunks = [];

	/**
	 * Sets the block state at the given position.
	 *
	 * @param Point3D $pos
	 * @param BlockState|null $blockState
	 */
	function set(Point3D $pos, ?BlockState $blockState = null): void
	{
		$x = (int) floor($pos->x);
		$z = (int) floor($pos->z);
		$chunk_x = (int) floor($pos->x / 16);
		$chunk_z = (int) floor($pos->z / 16);
		$this->getChunk($chunk_x, $chunk_z)
			 ->set($x, (int) floor($pos->y), $z, $blockState);
	}

	/**
	 * Gets a chunk, generating it if needed.
	 *
	 * @param int $x
	 * @param int $z
	 * @return Chunk
	 */
	function getChunk(int $x, int $z): Chunk
	{
		return array_key_exists("$x:$z", $this->chunks) ? $this->chunks["$x:$z"] : $this->chunkGenerator->generate($x, $z);
	}

	/**
	 * @param Structure $structure
	 * @param Point3D $origin
	 * @return void
	 */
	function apply(Structure $structure, Point3D $origin): void
	{
		$origin_x = (int) floor($origin->x);
		$origin_y = (int) floor($origin->y);
		$origin_z = (int) floor($origin->z);
		$chunk = null;
		for($x = 0; $x < $structure->width; $x++)
		{
			for($z = 0; $z < $structure->depth; $z++)
			{
				for($y = 0; $y < $structure->height; $y++)
				{
					$absolute_x = $origin_x + $x;
					$absolute_z = $origin_z + $z;
					$chunk_x = (int) floor($absolute_x / 16);
					$chunk_z = (int) floor($absolute_z / 16);
					if(!$chunk instanceof Chunk || $chunk_x !== $chunk->x || $chunk_z !== $chunk->z)
					{
						$chunk = $this->getChunk($chunk_x, $chunk_z);
					}
					$chunk->set($absolute_x, $origin_y + $y, $absolute_z, $structure->blocks[$x + ($z * $structure->width) + ($y * $structure->width * $structure->depth)]);
				}
			}
		}
	}
}
