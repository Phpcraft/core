<?php
namespace Phpcraft\World;
use Phpcraft\
{Connection, Exception\IOException, NBT\CompoundTag, NBT\IntTag, NBT\ListTag, NBT\StringTag};
class Structure
{
	/**
	 * @var int $width
	 */
	public $width;
	/**
	 * @var int $height
	 */
	public $height;
	/**
	 * @var int $depth
	 */
	public $depth;
	/**
	 * @var array $blocks
	 */
	public $blocks;

	function __construct(int $width, int $height, int $depth, array $blocks)
	{
		$this->width = $width;
		$this->height = $height;
		$this->depth = $depth;
		$this->blocks = $blocks;
	}

	/**
	 * Initiates a Structure object from a Connection, the read_buffer of which may be a file's zlib_decode'd contents.
	 *
	 * @link https://minecraft.gamepedia.com/Structure_block_file_format
	 * @param Connection $con
	 * @return Structure
	 * @throws IOException
	 */
	static function fromStructure(Connection $con): Structure
	{
		$nbt = $con->readNBT();
		if(!$nbt instanceof CompoundTag)
		{
			throw new IOException("Invalid structure root: ".strval($nbt));
		}
		if(!$nbt->hasChild("size"))
		{
			throw new IOException("Structure is missing size data");
		}
		$size = $nbt->getChild("size");
		if(!$size instanceof ListTag || count($size->children) != 3 || !$size->children[0] instanceof IntTag || !$size->children[1] instanceof IntTag || !$size->children[2] instanceof IntTag)
		{
			throw new IOException("Invalid structure size data: ".$size);
		}
		if(!$nbt->hasChild("blocks"))
		{
			throw new IOException("Structure is missing blocks data");
		}
		$blocks_nbt = $nbt->getChild("blocks");
		if(!$blocks_nbt instanceof ListTag)
		{
			throw new IOException("Invalid structure blocks data: ".$blocks_nbt);
		}
		if($nbt->hasChild("palette"))
		{
			$palette_nbt = $nbt->getChild("palette");
		}
		else if($nbt->hasChild("palettes") && count($nbt->getChild("palettes")->children) > 0)
		{
			$palette_nbt = $nbt->getChild("palettes")->children[0];
		}
		else
		{
			throw new IOException("Structure is missing palette data");
		}
		if(!$palette_nbt instanceof ListTag)
		{
			throw new IOException("Invalid structure palette data: ".$palette_nbt);
		}
		$palette = [];
		foreach($palette_nbt->children as $i => $child)
		{
			if(!$child instanceof CompoundTag || !$child->hasChild("Name"))
			{
				throw new IOException("Invalid palette entry: ".$child);
			}
			$block = Block::get($child->getChild("Name")->value);
			if(!$block instanceof Block)
			{
				throw new IOException("Unable to pinpoint block: ".$child->getChild("Name"));
			}
			$properties = [];
			if($child->hasChild("Properties"))
			{
				if(!$child->getChild("Properties") instanceof CompoundTag)
				{
					throw new IOException("Invalid palette entry: ".$child);
				}
				foreach($child->getChild("Properties")->children as $property)
				{
					if(!$property instanceof StringTag)
					{
						throw new IOException("Invalid block property: ".$property);
					}
					$properties[$property->name] = $property->value;
				}
			}
			$state = $block->getStateFromArray($properties);
			if(!$state instanceof BlockState)
			{
				throw new IOException("Unable to pinpoint block state: ".$child);
			}
			$palette[$i] = $state;
		}
		$width = gmp_intval($size->children[0]->value);
		$height = gmp_intval($size->children[1]->value);
		$depth = gmp_intval($size->children[2]->value);
		$blocks = array_fill(0, $width * $height * $depth, BlockState::get("air"));
		foreach($blocks_nbt->children as $block)
		{
			if(!$block instanceof CompoundTag || !$block->getChild("pos") || !$block->getChild("state"))
			{
				throw new IOException("Invalid block entry: ".$block);
			}
			$pos = $block->getChild("pos");
			if(!$pos instanceof ListTag || count($pos->children) != 3 || !$pos->children[0] instanceof IntTag || !$pos->children[1] instanceof IntTag || !$pos->children[2] instanceof IntTag)
			{
				throw new IOException("Invalid block pos: ".$pos);
			}
			// TODO: read block entity nbt
			$blocks[gmp_intval($pos->children[0]->value) + (gmp_intval($pos->children[2]->value) * $width) + (gmp_intval($pos->children[1]->value) * $width * $depth)] = $palette[gmp_intval($block->getChild("state")->value)];
		}
		return new Structure($width, $height, $depth, $blocks);
	}
}
