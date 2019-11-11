<?php
namespace Phpcraft;
use InvalidArgumentException;
class Block
{
	private static $all_cache;
	/**
	 * @var string $name
	 */
	public $name;
	/**
	 * @var int $since_protocol_version
	 */
	public $since_protocol_version;
	/**
	 * @var string|null $display_name
	 */
	public $display_name;
	/**
	 * @var array<BlockProperty> $properties Associative array of block properties.
	 */
	public $properties = [];
	public $states = [];
	public $legacy_id;

	protected function __construct(string $name, int $since_protocol_version, $display_name)
	{
		$this->name = $name;
		$this->since_protocol_version = $since_protocol_version;
		$this->display_name = $display_name;
	}

	/**
	 * Returns a Block by its name or null if not found.
	 *
	 * @param string $name
	 * @return Block|null
	 */
	static function get(string $name): ?Block
	{
		$name = strtolower($name);
		if(substr($name, 0, 10) == "minecraft:")
		{
			$name = substr($name, 10);
		}
		return @self::all()[$name];
	}

	/**
	 * Returns an array containing every Block.
	 *
	 * @return array<Block>
	 */
	static function all(): array
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [];
			$json_cache = [
				"1.13" => json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/1.13/blocks.json"), true)
			];
			foreach([
				393 => "1.13",
				397 => "1.13.2",
				477 => "1.14"
			] as $pv => $v)
			{
				foreach(json_decode(file_get_contents(Phpcraft::DATA_DIR."/mcdata/{$v}/blocks.json"), true) as $_identifier => $data)
				{
					$identifier = substr($_identifier, 10);
					if($pv == 393 || !array_key_exists($identifier, self::$all_cache))
					{
						$display_name = null;
						foreach($json_cache["1.13"] as $_block)
						{
							if($_block["name"] == $identifier)
							{
								$display_name = $_block["displayName"];
							}
						}
						$block = new Block($identifier, $pv, $display_name);
						$has_properties = array_key_exists("properties", $data);
						if($has_properties)
						{
							foreach($data["properties"] as $name => $values)
							{
								$block->properties[$name] = new BlockProperty($values);
							}
						}
						$state_i = 0;
						foreach($data["states"] as $state)
						{
							if($has_properties && array_key_exists("default", $state))
							{
								foreach($state["properties"] as $name => $value)
								{
									$block->properties[$name]->default = array_search($value, $block->properties[$name]->values);
								}
							}
							$ids = [
								477 => null,
								397 => null,
								393 => null
							];
							$ids[$pv] = $state["id"];
							foreach([
								47 => "1.8",
								107 => "1.9",
								210 => "1.10",
								314 => "1.11",
								328 => "1.12"
							] as $_pv => $_v)
							{
								if(!array_key_exists($_v, $json_cache))
								{
									$json_cache[$_v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$_v}/blocks.json"), true);
								}
								foreach($json_cache[$_v] as $_block)
								{
									if($_block["displayName"] === $display_name)
									{
										$block->legacy_id = $_block["id"];
										$block->since_protocol_version = $_pv;
										break 2;
									}
								}
							}
							array_push($block->states, new BlockState($block, $state_i++, $state["properties"] ?? [], $ids));
						}
						self::$all_cache[$identifier] = $block;
					}
					else
					{
						$state_i = 0;
						foreach($data["states"] as $state)
						{
							self::$all_cache[$identifier]->states[$state_i++]->ids[$pv] = $state["id"];
						}
					}
				}
			}
		}
		return self::$all_cache;
	}

	/**
	 * Gets a BlockState using the given property string.
	 *
	 * @param string $state_string A string in the format of "[facing=west][waterlogged=true]". Default values will be used for properties that are not given.
	 * @return BlockState
	 * @throws InvalidArgumentException If an invalid state string, property name, or property value was given.
	 */
	function getState(string $state_string = ""): BlockState
	{
		if(strlen($state_string) > 0 && (substr($state_string, 0, 1) != "[" || substr($state_string, -1) != "]"))
		{
			throw new InvalidArgumentException("Invalid property string: ".$state_string);
		}
		$properties = [];
		if(strlen($state_string) > 2)
		{
			foreach(explode("][", strtolower(substr($state_string, 1, -1))) as $_property)
			{
				$property = explode("=", $_property);
				if(count($property) != 2)
				{
					throw new InvalidArgumentException("Invalid property: ".$_property);
				}
				$properties[$property[0]] = $property[1];
			}
		}
		return $this->getStateFromArray($properties);
	}

	/**
	 * Gets a BlockState using the given associative string array.
	 *
	 * @param array<string,string> $properties Associative array containing the properties of the state. Default values will be used for properties that are not given.
	 * @return BlockState|null
	 * @throws InvalidArgumentException If an invalid property name or value was given.
	 */
	function getStateFromArray(array $properties): ?BlockState
	{
		$properties_ = [];
		foreach($this->properties as $name => $property)
		{
			$properties_[$name] = $properties[$name] ?? $property->getDefaultValue();
		}
		foreach($properties_ as $name => $value)
		{
			if(!array_key_exists($name, $this->properties))
			{
				throw new InvalidArgumentException("Invalid property for {$this->name}: $name");
			}
			if(!in_array($value, $this->properties[$name]->values))
			{
				throw new InvalidArgumentException("Invalid value $value for property $name");
			}
		}
		foreach($this->states as $state)
		{
			if($state->properties === $properties_)
			{
				return $state;
			}
		}
		return null;
	}
}
