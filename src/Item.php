<?php
namespace Phpcraft;
use Phpcraft\Nbt\NbtTag;
class Item extends Identifier
{
	private static $all_cache;
	public $block;
	private $legacy_id;

	protected function __construct(string $name, int $legacy_id, int $since_protocol_version = 0, string $block = null)
	{
		parent::__construct($name, $since_protocol_version);
		$this->legacy_id = $legacy_id;
		$this->block = $block;
	}

	/**
	 * Returns every Item.
	 *
	 * @return Item[]
	 * @todo Actually return *every* Item.
	 */
	public static function all()
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [
				new Item("air", 0),
				new Item("stone", 1 << 4, 0, "stone"),
				new Item("grass_block", 2 << 4, 0, "grass_block"),
				new Item("dirt", 3 << 4, 0, "dirt"),
				new Item("filled_map", 358 << 4)
			];
		}
		return self::$all_cache;
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer
	 */
	public function getId(int $protocol_version)
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
					return 8;
				case "dirt":
					return 9;
				case "filled_map":
					return 613;
			}
		}
		return null;
	}

	/**
	 * Returns the related block material.
	 *
	 * @return Material
	 */
	public function getBlock()
	{
		return $this->block == null ? null : Material::get($this->block);
	}

	/**
	 * Creates a slot containing this item.
	 *
	 * @param integer $count How many times this item is in the slot.
	 * @param NbtTag $nbt The NBT data of this item in the slot.
	 * @return Slot
	 */
	public function slot(int $count = 1, NbtTag $nbt = null)
	{
		return new Slot($this, $count, $nbt);
	}
}
