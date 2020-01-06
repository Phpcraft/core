<?php
namespace Phpcraft\World;
/**
 * @since 0.5 Moved from Phpcraft to Phpcraft\World namespace
 */
class BlockState
{
	private static $all_cache;
	/**
	 * @var Block $block
	 */
	public $block;
	/**
	 * @var array<BlockProperty> $properties
	 */
	public $properties;
	/**
	 * @var array<int,int> $ids
	 */
	public $ids;
	private $state_i;

	function __construct(Block &$block, int $state_i, array $properties, array $ids)
	{
		$this->block = $block;
		$this->state_i = $state_i;
		$this->properties = $properties;
		$this->ids = $ids;
	}

	/**
	 * Returns a BlockState by its ID in the given protocol version or null if not found.
	 *
	 * @param int $id
	 * @param int $protocol_version
	 * @return BlockState|null
	 */
	static function getById(int $id, int $protocol_version): ?BlockState
	{
		$i = 0;
		foreach(BlockState::all() as $blockState)
		{
			if(!$blockState instanceof BlockState)
			{
				var_dump($blockState);
				echo "@ $i\n";
			}
			if($blockState->getId($protocol_version) == $id)
			{
				return $blockState;
			}
			$i++;
		}
		return null;
	}

	/**
	 * Returns an array containing every BlockState.
	 *
	 * @return array<BlockState>
	 */
	static function all(): array
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [];
			foreach(Block::all() as $block)
			{
				self::$all_cache = array_merge(self::$all_cache, $block->states);
			}
		}
		return self::$all_cache;
	}

	/**
	 * Returns the ID of this BlockState for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	function getId(int $protocol_version): ?int
	{
		if($protocol_version >= $this->block->since_protocol_version)
		{
			if($protocol_version < 346)
			{
				return ($this->block->legacy_id << 4) | $this->getLegacyMetadata();
			}
			foreach($this->ids as $pv => $id)
			{
				if($protocol_version >= $pv)
				{
					return $id;
				}
			}
		}
		return null;
	}

	function getLegacyMetadata(): int
	{
		switch($this->block->name)
		{
			case "grass_block":
				return 0;
			default:
				return $this->state_i;
		}
	}

	function __toString()
	{
		return $this->block->name.$this->getStateString();
	}

	function getStateString(): string
	{
		$str = "";
		foreach($this->properties as $name => $value)
		{
			$str .= "[$name=$value]";
		}
		return $str;
	}

	/**
	 * Gets a replacement BlockState compatible with the given protocol_version, or $this if it's compatible.
	 *
	 * @param int $protocol_version
	 * @return BlockState
	 * @since 0.5
	 */
	function getCompatible(int $protocol_version): BlockState
	{
		return $this->block->since_protocol_version > $protocol_version ? BlockState::get("bedrock") : $this;
	}

	/**
	 * Returns a BlockState by its string representation, null if the block is not found, or throws an exception if an invalid state is given.
	 *
	 * @param string $str A BlockState string representation, e.g. "grass_block[snowy=true]"
	 * @return BlockState|null
	 */
	static function get(string $str): ?BlockState
	{
		$state_start = strpos($str, "[");
		$block = Block::get($state_start ? substr($str, 0, $state_start) : $str);
		return $block ? $block->getState($state_start ? substr($str, $state_start) : "") : null;
	}
}
