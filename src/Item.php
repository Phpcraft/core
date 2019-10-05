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
	private $ids;

	protected function __construct(string $name, int $since_protocol_version, array $ids, int $stack_size, string $display_name)
	{
		parent::__construct($name, $since_protocol_version);
		$this->ids = $ids;
		$this->stack_size = $stack_size;
		$this->display_name = $display_name;
	}

	/**
	 * Returns an Identifier by its name or null if not found.
	 *
	 * @param string $name
	 * @return Item|null
	 */
	static function get(string $name)
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
	static function all(): array
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [];
			$json_cache = [];
			foreach([
				393 => "1.13",
				397 => "1.13.2",
				477 => "1.14"
			] as $pv => $v)
			{
				foreach(json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/items.json"), true) as $item)
				{
					if($pv == 393 || !array_key_exists($item["name"], self::$all_cache))
					{
						$since_pv = $pv;
						$ids = [
							477 => null,
							397 => null,
							393 => null,
							0 => null
						];
						$ids[$pv] = $item["id"];
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
								$file_name = "{$_v}/{$type}";
								if(!array_key_exists($file_name, $json_cache))
								{
									$json_cache["{$_v}/{$type}"] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$_v}/{$type}.json"), true);
								}
								foreach($json_cache[$file_name] as $_item)
								{
									if(array_key_exists("variations", $_item))
									{
										foreach($_item["variations"] as $variation)
										{
											if($variation["displayName"] == $item["displayName"])
											{
												$ids[0] = ($_item["id"] << 4) | $variation["metadata"];
												$since_pv = $_pv;
												break 4;
											}
										}
									}
									else if($_item["name"] == $item["name"])
									{
										$ids[0] = $_item["id"] << 4;
										$since_pv = $_pv;
										break 3;
									}
								}
							}
						}
						self::$all_cache[$item["name"]] = new Item($item["name"], $since_pv, $ids, $item["stackSize"], $item["displayName"]);
					}
					else
					{
						self::$all_cache[$item["name"]]->ids[$pv] = $item["id"];
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
	 * @return integer|null
	 */
	function getId(int $protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			foreach($this->ids as $pv => $id)
			{
				if($protocol_version >= $pv)
				{
					return $id;
				}
			}
		}
		return null;
	}

	/**
	 * Returns the related Block.
	 *
	 * @return Block
	 */
	function getBlock(): Block
	{
		return Block::get($this->name);
	}

	/**
	 * Creates a slot containing this item.
	 *
	 * @param integer $count How many times this item is in the slot.
	 * @param NbtTag $nbt The NBT data of this item in the slot.
	 * @return Slot
	 */
	function slot(int $count = 1, NbtTag $nbt = null): Slot
	{
		return new Slot($this, $count, $nbt);
	}
}
