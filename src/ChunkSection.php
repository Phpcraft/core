<?php
namespace Phpcraft;
/**
 * @since 0.4.3
 */
class ChunkSection
{
	/**
	 * @var array<BlockState> $blocks
	 */
	public $blocks;
	/**
	 * @var int $non_air_blocks
	 */
	public $non_air_blocks;

	/**
	 * @param BlockState|null $fill The BlockState to be used for all 4096 blocks in the section. Defaults to air.
	 */
	function __construct(?BlockState $fill = null)
	{
		if($fill === null)
		{
			$fill = BlockState::get("air");
		}
		$this->blocks = array_fill(0, 4096, $fill);
		$this->non_air_blocks = ($fill->block->name == "air") ? 0 : 4096;
	}
}
