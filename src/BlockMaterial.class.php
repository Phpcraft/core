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
