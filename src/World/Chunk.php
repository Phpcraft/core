<?php
namespace Phpcraft\World;
use Phpcraft\
{Connection, Exception\IOException, NBT\CompoundTag, NBT\LongArrayTag};
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

	function getSectionAt(int $y): ChunkSection
	{
		return $this->getSection(floor($y / 16));
	}

	function getSection(int $index): ChunkSection
	{
		if($this->sections[$index] === null)
		{
			$this->sections[$index] = ChunkSection::filled();
			$this->sections_bit_mask |= (1 << $index);
		}
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
		$this->flagChanged();
	}

	/**
	 * Flags the chunk as changed, e.g. for the server to re-send to clients.
	 *
	 * @return void
	 */
	function flagChanged(): void
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
		$this->flagChanged();
	}

	function getIndexes(int $x, int $y, int $z): array
	{
		$section = (int) floor($y / 16);
		if($this->sections[$section] === null)
		{
			$this->sections[$section] = ChunkSection::filled();
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
	 * Reads the chunk data after the X, Z, "Is New Chunk" and "Ignore Old Data" fields from the given Connection.
	 *
	 * @param Connection $con
	 * @return void
	 * @throws IOException
	 */
	function read(Connection $con): void
	{
		if($con->protocol_version >= 70)
		{
			$sections_bit_mask = $con->readVarInt();
		}
		else if($con->protocol_version >= 60)
		{
			$sections_bit_mask = $con->readInt();
		}
		else
		{
			$sections_bit_mask = $con->readUnsignedShort();
		}
		if($con->protocol_version >= 472)
		{
			$con->readNBT(); // Heightmap
			if($con->protocol_version >= 565)
			{
				$con->ignoreBytes(4096); // Biomes
			}
		}
		$con->readVarInt(); // Data length
		for($i = 0; $i < 16; $i++)
		{
			if(($sections_bit_mask & (1 << $i)) == 0)
			{
				continue;
			}
			$section = new ChunkSection();
			if($con->protocol_version > 47)
			{
				if($con->protocol_version >= 472)
				{
					$section->non_air_blocks = $con->readShort();
				}
				$bits_per_block = $con->readByte();
				if($bits_per_block < 4)
				{
					if($con->leniency == Connection::LENIENCY_STRICT)
					{
						throw new IOException("Bits per block should at least be 4, got $bits_per_block");
					}
					$bits_per_block = 4;
				}
				$palette = [];
				if($bits_per_block < 9)
				{
					$palette_size = $con->readVarInt();
					for($j = 0; $j < $palette_size; $j++)
					{
						$palette[$j] = BlockState::getById(gmp_intval($con->readVarInt()), $con->protocol_version);
					}
					$section->palette_cache = [];
					foreach($palette as $j => $state)
					{
						$state_fqn = $state->__toString();
						if(array_key_exists($state_fqn, $section->palette_cache))
						{
							if($con->leniency == Connection::LENIENCY_STRICT)
							{
								throw new IOException("Duplicate palette entry at index $j: $state_fqn");
							}
							continue;
						}
						$section->palette_cache[$state_fqn] = $state;
					}
				}
				$longs = (4096 / (64 / $bits_per_block));
				$remote_longs = gmp_intval($con->readVarInt());
				if($remote_longs != $longs)
				{
					throw new IOException("$remote_longs longs doesn't match expected $longs longs for $bits_per_block bits per block");
				}
				$bits = "";
				for($j = 0; $j < $longs; $j++)
				{
					$bits .= gmp_strval($con->readGMP(8, 64, false, GMP_LSW_FIRST | GMP_BIG_ENDIAN), 2);
				}
				if($bits_per_block < 9)
				{
					for($j = 0; $j < 4096; $j++)
					{
						$section->blocks[$j] = $palette[bindec(strrev(substr($bits, $j * $bits_per_block, $bits_per_block)))];
					}
				}
				else
				{
					for($j = 0; $j < 4096; $j++)
					{
						$section->blocks[$j] = BlockState::getById(bindec(strrev(substr($bits, $j * $bits_per_block, $bits_per_block))), $con->protocol_version);
					}
				}
			}
			else
			{
				for($j = 0; $j < 4096; $j++)
				{
					$section->blocks[$j] = BlockState::getById(gmp_intval($con->readGMP(2, 16, false, GMP_LSW_FIRST | GMP_LITTLE_ENDIAN)), $con->protocol_version);
				}
				$bits_of_data_per_block = $con->readVarInt(); // Bits of data per block: 4 for block light, 8 for block + sky light, 16 for both + biome.
				for($j = $con->readVarInt(); $j > 0; $j--)
				{
					$con->readVarInt(); // Elements in block + sky light arrays
				}
				if($bits_of_data_per_block >= 16)
				{
					$con->ignoreBytes(256); // Biomes
				}
			}
			if($con->protocol_version < 472)
			{
				$section->non_air_blocks = 0;
				foreach($section->blocks as $state)
				{
					if($state->block->name != "air")
					{
						$section->non_air_blocks++;
					}
				}
			}
		}
		if($con->protocol_version >= 110) // Block entities
		{
			for($i = $con->readVarInt(); $i > 0; $i--)
			{
				$con->readNBT();
			}
		}
	}

	/**
	 * Writes the chunk data after the X, Z, "Is New Chunk" and "Ignore Old Data" fields to the given Connection.
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
			$con->writeUnsignedShort($this->sections_bit_mask);
		}
		if($con->protocol_version >= 472) // Heightmap
		{
			$this->getHeightmap()
				 ->write($con);
			if($con->protocol_version >= 565) // Biomes
			{
				$con->write_buffer .= str_repeat("\x00\x00\x00\x7F", 1024);
			}
		}
		$data = new Connection();
		foreach($this->sections as $section)
		{
			if(!$section instanceof ChunkSection)
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
						$bits_per_block = ceil(log(count(BlockState::all()), 2));
						break;
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
						$bits .= strrev(str_pad(decbin($palette[$section->blocks[$i]->__toString()]), $bits_per_block, "0", STR_PAD_LEFT));
					}
				}
				else
				{
					for($i = 0; $i < 4096; $i++)
					{
						$bits .= strrev(str_pad(decbin($section->blocks[$i]->getCompatible($con->protocol_version)
																		   ->getId($con->protocol_version)), $bits_per_block, "0", STR_PAD_LEFT));
					}
				}
				for($i = 0; $i < $longs; $i++)
				{
					$data->writeGMP(gmp_init(strrev(substr($bits, $i * 64, 64)), 2), 8, 64, false, GMP_LSW_FIRST | GMP_BIG_ENDIAN);
				}
			}
			else
			{
				for($i = 0; $i < 4096; $i++)
				{
					$data->writeGMP($section->blocks[$i]->getCompatible(47)
														->getId(47), 2, 16, false, GMP_LSW_FIRST | GMP_LITTLE_ENDIAN);
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
		if($con->protocol_version < 565)
		{
			$data->write_buffer .= str_repeat($con->protocol_version >= 357 ? "\x00\x00\x00\x7F" : "\x00", 256); // Biomes
		}
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
