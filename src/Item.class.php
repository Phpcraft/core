<?php
namespace Phpcraft;
class Item extends Material
{
	/**
	 * @copydoc Material::all
	 */
	static function all()
	{
		return [
			new Item("air",0,0,0),
			new Item("stone",1,1,0,"stone"),
			new Item("grass_block",8,2,0,"grass_block"),
			new Item("dirt",9,3,0,"dirt"),
			new Item("filled_map",613,358,0),
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
	function __construct($name, $id, $legacy_id, $legacy_metadata, $block = null)
	{
		parent::__construct($name, $id, $legacy_id, $legacy_metadata);
		$this->block = $block;
	}

	/**
	 * Returns the related block material.
	 * @return BlockMaterial
	 */
	function getBlock()
	{
		return $this->block == null ? null : \Phpcraft\BlockMaterial::get($this->block);
	}
}
