<?php
namespace Phpcraft;
class BlockMaterial extends Identifier
{
	private static $all_cache;
	private $legacy_id;
	/**
	 * The name of each Item dropped when this block is destroyed.
	 * @var array $drops
	 */
	public $drops;

	/**
	 * @copydoc Identifier::all
	 */
	public static function all()
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [
				new BlockMaterial("air", 0),
				new BlockMaterial("stone", 1 << 4, 0, ["stone"]),
				new BlockMaterial("grass_block", 2 << 4, 0, ["grass_block"]),
				new BlockMaterial("dirt", 3 << 4, 0, ["dirt"])
			];
		}
		return self::$all_cache;
	}

	private function __construct($name, $legacy_id, $since_protocol_version = 0, $drops = [])
	{
		$this->name = $name;
		$this->legacy_id = $legacy_id;
		$this->since_protocol_version = $since_protocol_version;
		$this->drops = $drops;
	}

	/**
	 * @copydoc Identifier::getId
	 */
	public function getId($protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			if($protocol_version < 346)
			{
				return $this->legacy_id;
			}
			switch($this->name)
			{
				case "air": return 0;
				case "stone": return 1;
				case "grass_block": return 9;
				case "dirt": return 10;
			}
		}
		return null;
	}

	/**
	 * Returns each Item that are supposed to be dropped when this block is destroyed.
	 * @return array
	 */
	public function getDrops()
	{
		$drops = [];
		foreach($this->drops as $name)
		{
			array_push($drops, Item::get($name));
		}
		return $drops;
	}
}
