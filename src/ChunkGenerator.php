<?php
namespace Phpcraft;
/**
 * @since 0.4.3
 */
abstract class ChunkGenerator
{
	/**
	 * @var array $options
	 */
	protected $options;

	/**
	 * @param array $options Generator-specific options
	 */
	function __construct(array $options = [])
	{
		$this->options = $options;
	}

	abstract function generate(int $x, int $z): Chunk;
}
