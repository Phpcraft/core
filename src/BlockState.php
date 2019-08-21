<?php
namespace Phpcraft;
class BlockState extends Identifier
{
	private static $all_cache;
	/**
	 * The name of each Item dropped when this block is destroyed.
	 *
	 * @var array $drops
	 */
	public $drops;
	private $legacy_id;

	protected function __construct(string $name, int $legacy_id, int $since_protocol_version = 0, array $drops = [])
	{
		parent::__construct($name, $since_protocol_version);
		$this->legacy_id = $legacy_id;
		$this->drops = $drops;
	}

	/**
	 * Returns every BlockState.
	 *
	 * @return BlockState[]
	 * @todo Actually return *every* BlockState.
	 */
	static function all(): array
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [
				new BlockState("air", 0),
				new BlockState("stone", 1 << 4, 0, ["stone"]),
				new BlockState("grass_block", 2 << 4, 0, ["grass_block"]),
				new BlockState("dirt", 3 << 4, 0, ["dirt"])
			];
		}
		return self::$all_cache;
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer|null
	 */
	function getId(int $protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			if($protocol_version < 346)
			{
				return $this->legacy_id;
			}
			switch($this->name)
			{
				case "air":
					return 0;
				case "stone":
					return 1;
				case "grass_block":
					return 9;
				case "dirt":
					return 10;
			}
		}
		return null;
	}

	/**
	 * Returns each Item that are supposed to be dropped when this block is destroyed.
	 *
	 * @return Item[]
	 */
	function getDrops(): array
	{
		$drops = [];
		foreach($this->drops as $name)
		{
			array_push($drops, Item::get($name));
		}
		return $drops;
	}
}
