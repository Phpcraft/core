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
	 * @copydoc Material::get
	 */
	static function get($arg)
	{
		if(gettype($arg) == "string")
		{
			$arg = strtolower($arg);
			if(substr($arg, 0, 10) == "minecraft:")
			{
				$arg = substr($arg, 10);
			}
			foreach(Item::all() as $material)
			{
				if($material->name == $arg)
				{
					return $material;
				}
			}
		}
		else if(gettype($arg) == "integer")
		{
			foreach(Item::all() as $material)
			{
				if($material->id == $id)
				{
					return $material;
				}
			}
		}
		else
		{
			throw new \Phpcraft\Exception("Item::get's argument needs to be either string or integer.");
		}
		return null;
	}

	/**
	 * @copydoc Material::getLegacy
	 */
	static function getLegacy($legacy_id, $legacy_metadata = 0)
	{
		foreach(Item::all() as $material)
		{
			if($material->legacy_id == $legacy_id && $material->legacy_metadata == $legacy_metadata)
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
