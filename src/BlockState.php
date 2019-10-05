<?php
namespace Phpcraft;
class BlockState
{
	private static $all_cache;
	/**
	 * @var Block $block
	 */
	public $block;
	/**
	 * @var array $properties
	 */
	public $properties;
	/**
	 * @var array $ids
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
	 * Returns a BlockState by its string representation, null if the block is not found, or throws an exception if an invalid state is given.
	 *
	 * @param string $str A BlockState string representation, e.g. "grass_block[snowy=true]"
	 * @return BlockState|null
	 */
	static function get(string $str)
	{
		$state_start = strpos($str, "[");
		$block = Block::get($state_start ? substr($str, 0, $state_start) : $str);
		return $block ? $block->getState($state_start ? substr($str, $state_start) : "") : null;
	}

	/**
	 * Returns a BlockState by its ID in the given protocol version or null if not found.
	 *
	 * @param integer $id
	 * @param integer $protocol_version
	 * @return BlockState|null
	 */
	static function getById(int $id, int $protocol_version)
	{
		foreach(BlockState::all() as $blockState)
		{
			if($blockState->getId($protocol_version) == $id)
			{
				return $blockState;
			}
		}
		return null;
	}

	/**
	 * Returns everything of this type.
	 *
	 * @return static[]
	 */
	static function all()
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

	/**
	 * Returns the ID of this BlockState for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer|null
	 */
	function getId(int $protocol_version)
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

	function __toString()
	{
		return $this->block->name.$this->getStateString();
	}

	function getStateString()
	{
		$str = "";
		foreach($this->properties as $name => $value)
		{
			$str .= "[$name=$value]";
		}
		return $str;
	}
}
