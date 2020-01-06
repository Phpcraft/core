<?php
namespace Phpcraft\World;
use Phpcraft\
{Connection, NBT\CompoundTag, NBT\LongArrayTag};
/**
 * @since 0.4.3
 * @since 0.5 Moved from Phpcraft to Phpcraft\World namespace
 */
class Chunk
{
	/**
	 * @var int|null $x
	 */
	public $x;
	/**
	 * @var int|null $z
	 */
	public $z;
	/**
	 * @var string|null $name
	 */
	public $name;
	/**
	 * @var World|null $world
	 */
	public $world;
	/**
	 * @var array<ChunkSection|null> $sections
	 */
	private $sections = [];
	private $sections_bit_mask = 0;
	private $heightmap_cache = null;

	function __construct(int $x = null, int $z = null)
	{
		$this->x = $x;
		$this->z = $z;
		$this->name = "$x:$z";
		$this->sections = [
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null
		];
	}

	function getSectionsBitMask(): int
	{
		return $this->sections_bit_mask;
	}

	function getSection(int $index): int
	{
		return $this->sections[$index];
	}

	function setSection(int $index, ChunkSection $section): void
	{
		$this->sections[$index] = $section;
		if($section !== null)
		{
			$this->sections_bit_mask |= (1 << $index);
		}
		else if($this->sections_bit_mask & (1 << $index))
		{
			$this->sections_bit_mask ^= (1 << $index);
		}
		$this->save();
	}

	private function save(): void
	{
		$this->heightmap_cache = null;
		if($this->world instanceof World)
		{
			if(!array_key_exists($this->name, $this->world->chunks))
			{
				$this->world->chunks[$this->name] = $this;
			}
			$this->world->changed_chunks[$this->name] = $this->name;
		}
	}

	function copy(int $x, int $z): Chunk
	{
		$chunk = clone $this;
		$chunk->x = $x;
		$chunk->z = $z;
		$chunk->name = "$x:$z";
		return $chunk;
	}

	/**
	 * Sets the block state at the given position.
	 *
	 * @param int $x Absolute X position of the block within the world.
	 * @param int $y
	 * @param int $z Absolute Z position of the block within the world.
	 * @param BlockState|null $blockState
	 */
	function set(int $x, int $y, int $z, ?BlockState $blockState = null): void
	{
		[
			$section,
			$block
		] = $this->getIndexes($x, $y, $z);
		if($blockState === null)
		{
			$blockState = BlockState::get("air");
			$is_air = true;
		}
		else
		{
			$is_air = ($blockState->block->name == "air");
		}
		if(($this->sections[$section]->blocks[$block]->block->name == "air" ? 1 : 0) ^ ($is_air ? 1 : 0))
		{
			if($is_air)
			{
				$this->sections[$section]->non_air_blocks--;
			}
			else
			{
				$this->sections[$section]->non_air_blocks++;
			}
		}
		$this->sections[$section]->blocks[$block] = $blockState;
		$this->sections[$section]->palette_cache = null;
		$this->save();
	}

	function getIndexes(int $x, int $y, int $z): array
	{
		$section = (int) floor($y / 16);
		if($this->sections[$section] === null)
		{
			$this->sections[$section] = new ChunkSection();
			$this->sections_bit_mask |= (1 << $section);
		}
		$x -= ($this->x * 16);
		if($x < 0)
		{
			$x += 16;
		}
		$y -= ($section * 16);
		$z -= ($this->z * 16);
		if($z < 0)
		{
			$z += 16;
		}
		return [
			$section,
			$x + ($z * 16) + ($y * 256)
		];
	}

	/**
	 * Writes the chunk data after the X, Z, and "Is New Chunk" fields to the given Connection.
	 *
	 * @param Connection $con
	 * @return void
	 */
	function write(Connection $con): void
	{
		if($con->protocol_version >= 70)
		{
			$con->writeVarInt($this->sections_bit_mask);
		}
		else if($con->protocol_version >= 60)
		{
			$con->writeInt($this->sections_bit_mask);
		}
		else
		{
			$con->writeShort($this->sections_bit_mask);
		}
		if($con->protocol_version >= 472) // Heightmap
		{
			$this->getHeightmap()
				 ->write($con);
		}
		$data = new Connection();
		foreach($this->sections as $section)
		{
			if($section === null)
			{
				continue;
			}
			if($con->protocol_version > 47)
			{
				if($con->protocol_version >= 472)
				{
					$data->writeShort($section->non_air_blocks);
				}
				$palette = $section->getPalette();
				$bits_per_block = 4;
				while(count($palette) > pow(2, $bits_per_block))
				{
					if(++$bits_per_block > 8)
					{
						$bits_per_block = count(BlockState::all());
					}
				}
				$data->writeByte($bits_per_block);
				if($bits_per_block < 9)
				{
					$data->writeVarInt(count($palette));
					$i = 0;
					foreach($palette as $state_fqn => $state)
					{
						$data->writeVarInt($state->getCompatible($con->protocol_version)
												 ->getId($con->protocol_version));
						$palette[$state_fqn] = $i++;
					}
				}
				$longs = (4096 / (64 / $bits_per_block));
				$data->writeVarInt($longs);
				$bits = "";
				if($bits_per_block < 9)
				{
					for($i = 0; $i < 4096; $i++)
					{
						$bits .= str_pad(decbin($palette[$section->blocks[$i]->__toString()]), $bits_per_block, "0", STR_PAD_LEFT);
					}
				}
				else
				{
					for($i = 0; $i < 4096; $i++)
					{
						$bits .= str_pad(decbin($section->blocks[$i]->getCompatible($con->protocol_version)
																	->getId($con->protocol_version)), $bits_per_block, "0", STR_PAD_LEFT);
					}
				}
				for($i = 0; $i < $longs; $i++)
				{
					$data->writeGMP(gmp_init(substr($bits, $i * 64, 64), 2), 8, 64, false, GMP_MSW_FIRST | GMP_LITTLE_ENDIAN); // For some reason the bit order is reversed
				}
			}
			else
			{
				for($i = 0; $i < 4096; $i++)
				{
					$data->writeGMP($section->blocks[$i]->getCompatible(47)
														->getId(47), 2, 16, false, GMP_LSW_FIRST | GMP_BIG_ENDIAN); // For some reason the byte order is reversed
				}
				$data->writeVarInt(16); // Bits of data per block: 4 for block light, 8 for block + sky light, 16 for both + biome.
				$data->writeVarInt(8192); // Number of elements in block + sky light arrays
			}
			if($con->protocol_version < 472)
			{
				$data->write_buffer .= str_repeat("\x00", 2048); // Block Light
				$data->write_buffer .= str_repeat("\xFF", 2048); // Sky Light
			}
		}
		$data->write_buffer .= str_repeat($con->protocol_version >= 357 ? "\x00\x00\x00\x7F" : "\x00", 256); // Biomes
		$con->writeVarInt(strlen($data->write_buffer));
		$con->write_buffer .= $data->write_buffer;
		if($con->protocol_version >= 110)
		{
			$con->writeVarInt(0); // Number of block entities
		}
	}

	function getHeightmap(): CompoundTag
	{
		if($this->heightmap_cache === null)
		{
			$heightmap = array_fill(0, 256, 0);
			$pillar = 0;
			for($x = 0; $x < 16; $x++)
			{
				for($z = 0; $z < 16; $z++)
				{
					for($section = 15; $section >= 0; $section--)
					{
						if($this->sections[$section] instanceof ChunkSection)
						{
							for($y = 15; $y >= 0; $y--)
							{
								$state = $this->sections[$section]->blocks[$pillar + ($y * 256)];
								if($state instanceof BlockState && $state->block->name != "air")
								{
									$heightmap[$pillar++] = $y;
									continue 3;
								}
							}
						}
					}
					$pillar++;
				}
			}
			$heightmap_bits = "";
			foreach($heightmap as $y)
			{
				$heightmap_bits .= str_pad(decbin($y), 9, "0", STR_PAD_LEFT);
			}
			$motion_blocking = new LongArrayTag("MOTION_BLOCKING");
			for($i = 0; $i < 36; $i++)
			{
				array_push($motion_blocking->children, gmp_init(substr($heightmap_bits, $i * 64, 64), 2));
			}
			$this->heightmap_cache = (new CompoundTag("", [$motion_blocking]));
		}
		return $this->heightmap_cache;
	}
}
