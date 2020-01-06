<?php
namespace Phpcraft\World;
/**
 * @since 0.4.3
 * @since 0.5 Moved from Phpcraft to Phpcraft\World namespace
 */
abstract class ChunkGenerator
{
	/**
	 * @var World|null $world
	 */
	public $world;

	function __construct(World $world)
	{
		$this->world = $world;
	}

	/**
	 * Sets this ChunkGenerator to be the chunk generator of the World it belongs to and returns the world.
	 *
	 * @return World
	 */
	function init(): World
	{
		$this->world->chunkGenerator = $this;
		return $this->world;
	}

	abstract function generate(int $x, int $z): Chunk;
}
