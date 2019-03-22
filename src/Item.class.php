<?php
namespace Phpcraft;
class Item extends Identifier
{
	private $legacy_id;

	/**
	 * @copydoc Identifier::all
	 */
	static function all()
	{
		return [
			new Item("air", 0),
			new Item("stone", 1 << 4, 0, "stone"),
			new Item("grass_block", 2 << 4, 0, "grass_block"),
			new Item("dirt", 3 << 4, 0, "dirt"),
			new Item("filled_map", 358 << 4)
		];
	}

	/**
	 * The name of the related BlockMaterial.
	 * @var string $block;
	 */
	public $block;

	/**
	 * The constructor.
	 * @param string $name The name without minecraft: prefix.
	 * @param integer $legacy_id The pre-flattening ID of this item.
	 * @param integer $since_protocol_version The protocol version at which this item was introduced.
	 * @param string $block The name of the related BlockMaterial.
	 */
	function __construct($name, $legacy_id, $since_protocol_version = 0, $block = null)
	{
		$this->name = $name;
		$this->legacy_id = $legacy_id;
		$this->since_protocol_version = $since_protocol_version;
		$this->block = $block;
	}

	/**
	 * @copydoc Identifier::getId
	 */
	function getId($protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			if($protocol_version >= 346)
			{
				switch($this->name)
				{
					case "air": return 0;
					case "stone": return 1;
					case "grass_block": return 8;
					case "dirt": return 9;
					case "filled_map": return 613;
				}
			}
			else
			{
				return $this->legacy_id;
			}
		}
		return null;
	}

	/**
	 * Returns the related block material.
	 * @return BlockMaterial
	 */
	function getBlock()
	{
		return $this->block == null ? null : BlockMaterial::get($this->block);
	}
}
