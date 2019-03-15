<?php
namespace Phpcraft;
class Item extends Identifier
{
	/**
	 * @copydoc Material::all
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
	 * The name of the related block material.
	 * @var string $block;
	 */
	public $block;

	/**
	 * @copydoc Material::__construct
	 * @param string $block The name of the related block material.
	 */
	function __construct($name, $legacy_id, $since_protocol_version = 0, $block = null)
	{
		parent::__construct($name, $legacy_id, $since_protocol_version);
		$this->block = $block;
	}

	/**
	 * @copydoc Identifier::getId
	 */
	function getId($protocol_version)
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
		else if($protocol_version >= $this->since_protocol_version)
		{
			return $this->legacy_id;
		}
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
