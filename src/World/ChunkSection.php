<?php
namespace Phpcraft\World;
/**
 * @since 0.4.3
 * @since 0.5 Moved from Phpcraft to Phpcraft\World namespace
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
	 * @var array|null
	 */
	public $palette_cache = null;

	/**
	 * @param BlockState|null $filler The BlockState to be used for all 4096 blocks in the section. Defaults to air.
	 * @return ChunkSection
	 * @since 0.5.5
	 */
	static function filled(?BlockState $filler = null): ChunkSection
	{
		if($filler === null)
		{
			$filler = BlockState::get("air");
		}
		$section = new ChunkSection();
		$section->blocks = array_fill(0, 4096, $filler);
		$section->non_air_blocks = ($filler->block->name == "air") ? 0 : 4096;
		return $section;
	}

	function getPalette(): array
	{
		if($this->palette_cache === null)
		{
			$this->palette_cache = [];
			$i = 0;
			for($x = 0; $x < 16; $x++)
			{
				for($z = 0; $z < 16; $z++)
				{
					for($y = 0; $y < 16; $y++)
					{
						$state = $this->blocks[$i++];
						$state_fqn = $state->__toString();
						if(!array_key_exists($state_fqn, $this->palette_cache))
						{
							$this->palette_cache[$state_fqn] = $state;
						}
					}
				}
			}
		}
		return $this->palette_cache;
	}
}
