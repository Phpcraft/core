<?php
namespace Phpcraft;
class BlockMaterial extends Material
{
	/**
	 * @copydoc Material::all
	 */
	static function all()
	{
		return [
			new BlockMaterial("air",0,0,0),
			new BlockMaterial("stone",1,1,0,["stone"]),
			new BlockMaterial("grass_block",9,2,0,"grass_block"),
			new BlockMaterial("dirt",10,3,0,"dirt"),
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
			foreach(BlockMaterial::all() as $material)
			{
				if($material->name == $arg)
				{
					return $material;
				}
			}
		}
		else if(gettype($arg) == "integer")
		{
			foreach(BlockMaterial::all() as $material)
			{
				if($material->id == $id)
				{
					return $material;
				}
			}
		}
		else
		{
			throw new \Phpcraft\Exception("BlockMaterial::get's argument needs to be either string or integer.");
		}
		return null;
	}

	/**
	 * @copydoc Material::getLegacy
	 */
	static function getLegacy($legacy_id, $legacy_metadata = 0)
	{
		foreach(BlockMaterial::all() as $material)
		{
			if($material->legacy_id == $legacy_id && $material->legacy_metadata == $legacy_metadata)
			{
				return $material;
			}
		}
		return null;
	}

	/**
	 * The names of the item materials dropped when this block is destroyed.
	 * @var array $drops
	 */
	public $drops;

	/**
	 * @copydoc Material::__construct
	 * @param array $drops The names of the item materials dropped when this block is destroyed.
	 */
	function __construct($name, $id, $legacy_id, $legacy_metadata, $drops = [])
	{
		parent::__construct($name, $id, $legacy_id, $legacy_metadata);
	}

	/**
	 * Returns the item materials that are supposed to be dropped when this block is destroyed.
	 * @return array
	 */
	function getDrops()
	{
		$drops = [];
		foreach($this->drops as $name)
		{
			array_push($drops, \Phpcraft\Item::get($name));
		}
		return $drops;
	}
}
