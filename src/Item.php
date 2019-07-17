<?php
namespace Phpcraft;
use Phpcraft\Nbt\NbtTag;
class Item extends Identifier
{
	private static $all_cache;
	/**
	 * @var integer $stack_size
	 */
	public $stack_size;
	/**
	 * @var string $display_name
	 */
	public $display_name;
	private $legacy_id;

	protected function __construct(string $name, int $since_protocol_version, $legacy_id, int $stack_size, string $display_name)
	{
		parent::__construct($name, $since_protocol_version);
		$this->legacy_id = $legacy_id;
		$this->stack_size = $stack_size;
		$this->display_name = $display_name;
	}

	/**
	 * Returns an Identifier by its name or null if not found.
	 *
	 * @param string $name
	 * @return static
	 */
	public static function get(string $name)
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		return @self::all()[$name];
	}

	/**
	 * Returns every Item.
	 *
	 * @return Item[]
	 */
	public static function all()
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [];
			foreach([
				477 => "1.14",
				404 => "1.13.2",
				393 => "1.13"
			] as $pv => $v)
			{
				foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/{$v}/items.json") as $item)
				{
					if($pv == 477 || !array_key_exists($item["name"], self::$all_cache))
					{
						$since_pv = $pv;
						$legacy_id = null;
						foreach([
							47 => "1.8",
							107 => "1.9",
							210 => "1.10",
							314 => "1.11",
							328 => "1.12"
						] as $_pv => $_v)
						{
							foreach([
								"blocks",
								"items"
							] as $type)
							{
								foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/{$_v}/{$type}.json") as $_item)
								{
									if(array_key_exists("variations", $_item))
									{
										foreach($_item["variations"] as $variation)
										{
											if($variation["displayName"] == $item["displayName"])
											{
												$legacy_id = ($_item["id"] << 4) | $variation["metadata"];
												$since_pv = $_pv;
												break 4;
											}
										}
									}
									else if($_item["name"] == $item["name"])
									{
										$legacy_id = $_item["id"] << 4;
										$since_pv = $_pv;
										break 3;
									}
								}
							}
						}
						self::$all_cache[$item["name"]] = new Item($item["name"], $since_pv, $legacy_id, $item["stackSize"], $item["displayName"]);
					}
				}
			}
		}
		return self::$all_cache;
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer
	 */
	function getId(int $protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			if($protocol_version < 346)
			{
				return $this->legacy_id;
			}
			foreach([
				477 => "1.14",
				404 => "1.13.2",
				393 => "1.13"
			] as $pv => $v)
			{
				if($protocol_version < $pv)
				{
					continue;
				}
				foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/{$v}/items.json") as $item)
				{
					if($item["name"] == $this->name)
					{
						return $item["id"];
					}
				}
			}
		}
		return null;
	}

	/**
	 * Returns the related block material.
	 *
	 * @return Material
	 */
	function getBlock()
	{
		return Material::get($this->name);
	}

	/**
	 * Creates a slot containing this item.
	 *
	 * @param integer $count How many times this item is in the slot.
	 * @param NbtTag $nbt The NBT data of this item in the slot.
	 * @return Slot
	 */
	function slot(int $count = 1, NbtTag $nbt = null)
	{
		return new Slot($this, $count, $nbt);
	}
}
