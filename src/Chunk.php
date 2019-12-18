<?php
namespace Phpcraft;
use Phpcraft\NBT\
{CompoundTag, LongArrayTag};
use RuntimeException;
/**
 * @since 0.4.3
 */
class Chunk
{
	/**
	 * @var array<ChunkSection|null> $sections
	 */
	private $sections = [];
	private $sections_bit_mask = 0;
	private $heightmap_cache = null;
	private $palette_cache = null;

	function __construct()
	{
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
	}

	function set(Point3D $pos, ?BlockState $blockState = null): void
	{
		[
			$i,
			$j
		] = $this->getIndexes($pos);
		if($blockState === null)
		{
			$blockState = BlockState::get("air");
			$is_air = true;
		}
		else
		{
			$is_air = ($blockState->block->name == "air");
		}
		if(($this->sections[$i]->blocks[$j]->block->name == "air" ? 1 : 0) ^ ($is_air ? 1 : 0))
		{
			if($is_air)
			{
				$this->sections[$i]->non_air_blocks--;
			}
			else
			{
				$this->sections[$i]->non_air_blocks++;
			}
		}
		$this->sections[$i]->blocks[$j] = $blockState;
		$this->heightmap_cache = null;
		$this->palette_cache = null;
	}

	function getIndexes(Point3D $pos): array
	{
		$section = floor($pos->y / 16);
		if($this->sections[$section] === null)
		{
			$this->sections[$section] = new ChunkSection();
			$this->sections_bit_mask |= (1 << $section);
		}
		return [
			$section,
			$pos->x + ($pos->z * 16) + (($pos->y - ($section * 16)) * 256)
		];
	}

	function getPalette(): array
	{
		if($this->palette_cache === null)
		{
			$this->populateCaches();
		}
		return $this->palette_cache;
	}

	private function populateCaches(): void
	{
		$this->palette_cache = [];
		$heightmap = array_fill(0, 256, 0);
		for($i = 15; $i >= 0; $i--)
		{
			if($this->sections[$i] !== null)
			{
				for($x = 0; $x < 16; $x++)
				{
					for($z = 0; $z < 16; $z++)
					{
						for($y = 15; $y >= 0; $y--)
						{
							$j = $x + ($z * 16);
							$state = $this->sections[$i]->blocks[$j + ($y * 256)];
							$state_fqn = $state->__toString();
							if($heightmap[$j] == 0 && $state_fqn != "air")
							{
								$heightmap[$j] = $y;
							}
							if(!array_key_exists($state_fqn, $this->palette_cache))
							{
								$this->palette_cache[$state_fqn] = $state;
							}
						}
					}
				}
			}
		}
		$heightmap_bits = "";
		foreach($heightmap as $pillar)
		{
			$heightmap_bits .= str_pad(decbin($pillar), 9, "0", STR_PAD_LEFT);
		}
		$motion_blocking = new LongArrayTag("MOTION_BLOCKING");
		for($i = 0; $i < 36; $i++)
		{
			array_push($motion_blocking->children, gmp_init(substr($heightmap_bits, $i * 64, 64), 2));
		}
		$this->heightmap_cache = (new CompoundTag("", [$motion_blocking]));
	}

	function getHeightmap(): CompoundTag
	{
		if($this->heightmap_cache === null)
		{
			$this->populateCaches();
		}
		return $this->heightmap_cache;
	}

	/**
	 * @param Connection $con
	 */
	function write(Connection $con): void
	{
		if($con->protocol_version > 47 && ($this->heightmap_cache === null || $this->palette_cache === null))
		{
			$this->populateCaches();
		}
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
			assert($this->heightmap_cache instanceof CompoundTag);
			$this->heightmap_cache->write($con);
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
				$bits_per_block = 4;
				while(count($this->palette_cache) > pow(2, $bits_per_block))
				{
					$bits_per_block++;
				}
				if($bits_per_block > 8)
				{
					trigger_error("Palette exceeds 256 block states");
				}
				$data->writeByte($bits_per_block);
				$data->writeVarInt(count($this->palette_cache));
				$palette = [];
				$i = 0;
				foreach($this->palette_cache as $state_fqn => $state)
				{
					$data->writeVarInt($state->getId($con->protocol_version));
					$palette[$state_fqn] = $i++;
				}
				$data->writeVarInt(4096 / (64 / $bits_per_block));
				$bits = "";
				$next_byte = 8;
				for($i = 0; $i < 4096; $i++)
				{
					$bits .= str_pad(decbin($palette[$section->blocks[$i]->__toString()]), $bits_per_block, "0", STR_PAD_LEFT);
					if(strlen($bits) >= $next_byte)
					{
						$data->write_buffer .= pack("c", bindec(substr($bits, $next_byte - 8, 8)));
						$next_byte += 8;
					}
				}
			}
			else
			{
				for($i = 0; $i < 4096; $i++)
				{
					$data->writeGMP($section->blocks[$i]->getId(47), 2, 16, false, GMP_LSW_FIRST | GMP_BIG_ENDIAN); // For some reason the byte order is reversed
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
}
