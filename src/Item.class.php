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
			new Item("stone",1,1,0,"stone"),
			new Item("filled_map",613,358,0)
		];
	}

	/**
	 * @copydoc Material::get
	 */
	static function get($name)
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		foreach(Item::all() as $material)
		{
			if($material->name == $name)
			{
				return $material;
			}
		}
		return null;
	}

	/**
	 * Name of the related block material.
	 * @var string $block;
	 */
	public $block;

	/**
	 * @copydoc Material::__construct
	 * @param string $block Name of the related block material.
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
