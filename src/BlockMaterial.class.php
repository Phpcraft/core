<?php
namespace Phpcraft;
class BlockMaterial extends Identifier
{
	/**
	 * The names of the item materials dropped when this block is destroyed.
	 * @var array $drops
	 */
	public $drops;

	/**
	 * @copydoc Identifier::all
	 */
	static function all()
	{
		return [
			new BlockMaterial("air", 0),
			new BlockMaterial("stone", 1 << 4, ["stone"]),
			new BlockMaterial("grass_block", 2 << 4, ["grass_block"]),
			new BlockMaterial("dirt", 3 << 4, ["dirt"])
		];
	}

	/**
	 * @copydoc Identifier::__construct
	 * @param array $drops The names of the item materials dropped when this block is destroyed.
	 */
	function __construct($name, $legacy_id, $since_protocol_version = 0, $drops = [])
	{
		parent::__construct($name, $legacy_id, $since_protocol_version);
		$this->drops = $drops;
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
				case "grass_block": return 9;
				case "dirt": return 10;
			}
		}
		else if($protocol_version >= $this->since_protocol_version)
		{
			return $this->legacy_id;
		}
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
			array_push($drops, Item::get($name));
		}
		return $drops;
	}
}
