<?php
namespace Phpcraft\Entity;
use Phpcraft\
{Identifier, Phpcraft};
class EntityType extends Identifier
{
	protected static $all_cache;
	private static $json_cache = [];

	protected function __construct(string $name, int $since_protocol_version = 0)
	{
		parent::__construct($name, $since_protocol_version);
	}

	static protected function populateAllCache()
	{
		self::$all_cache = [
			"area_effect_cloud" => new EntityType("area_effect_cloud"),
			"armor_stand" => new EntityType("armor_stand"),
			"arrow" => new EntityType("arrow"),
			"bat" => new EntityType("bat"),
			"blaze" => new EntityType("blaze"),
			"boat" => new EntityType("boat"),
			"cave_spider" => new EntityType("cave_spider"),
			"chicken" => new EntityType("chicken"),
			"cod" => new EntityType("cod"),
			"cow" => new EntityType("cow"),
			"creeper" => new EntityType("creeper"),
			"donkey" => new EntityType("donkey"),
			"dolphin" => new EntityType("dolphin"),
			"dragon_fireball" => new EntityType("dragon_fireball"),
			"drowned" => new EntityType("drowned"),
			"elder_guardian" => new EntityType("elder_guardian", 301),
			"end_crystal" => new EntityType("end_crystal"),
			"ender_dragon" => new EntityType("ender_dragon"),
			"enderman" => new EntityType("enderman"),
			"endermite" => new EntityType("endermite"),
			"evoker_fangs" => new EntityType("evoker_fangs", 307),
			"evoker" => new EntityType("evoker", 307),
			"experience_orb" => new EntityType("experience_orb"),
			"eye_of_ender" => new EntityType("eye_of_ender"),
			"falling_block" => new EntityType("falling_block"),
			"firework_rocket" => new EntityType("firework_rocket"),
			"ghast" => new EntityType("ghast"),
			"giant" => new EntityType("giant"),
			"guardian" => new EntityType("guardian", 0),
			"horse" => new EntityType("horse"),
			"husk" => new EntityType("husk"),
			"illusioner" => new EntityType("illusioner"),
			"item" => new EntityType("item"),
			"item_frame" => new EntityType("item_frame"),
			"fireball" => new EntityType("fireball"),
			"leash_knot" => new EntityType("leash_knot"),
			"llama" => new EntityType("llama", 307),
			"llama_spit" => new EntityType("llama_spit", 311),
			"magma_cube" => new EntityType("magma_cube"),
			"minecart" => new EntityType("minecart"),
			"chest_minecart" => new EntityType("chest_minecart"),
			"command_block_minecart" => new EntityType("command_block_minecart"),
			"furnace_minecart" => new EntityType("furnace_minecart"),
			"hopper_minecart" => new EntityType("hopper_minecart"),
			"spawner_minecart" => new EntityType("spawner_minecart"),
			"tnt_minecart" => new EntityType("tnt_minecart"),
			"mule" => new EntityType("mule", 301),
			"mooshroom" => new EntityType("mooshroom"),
			"ocelot" => new EntityType("ocelot"),
			"painting" => new EntityType("painting"),
			"parrot" => new EntityType("parrot", 318),
			"pig" => new EntityType("pig"),
			"pufferfish" => new EntityType("pufferfish", 362),
			"zombie_pigman" => new EntityType("zombie_pigman"),
			"polar_bear" => new EntityType("polar_bear"),
			"tnt" => new EntityType("tnt"),
			"rabbit" => new EntityType("rabbit"),
			"salmon" => new EntityType("salmon"),
			"sheep" => new EntityType("sheep"),
			"shulker" => new EntityType("shulker", 49),
			"shulker_bullet" => new EntityType("shulker_bullet", 49),
			"silverfish" => new EntityType("silverfish"),
			"skeleton" => new EntityType("skeleton"),
			"skeleton_horse" => new EntityType("skeleton_horse", 301),
			"slime" => new EntityType("slime"),
			"small_fireball" => new EntityType("small_fireball"),
			"snow_golem" => new EntityType("snow_golem"),
			"snowball" => new EntityType("snowball"),
			"spectral_arrow" => new EntityType("spectral_arrow"),
			"spider" => new EntityType("spider"),
			"squid" => new EntityType("squid"),
			"stray" => new EntityType("stray"),
			"tropical_fish" => new EntityType("tropical_fish", 364),
			"turtle" => new EntityType("turtle"),
			"egg" => new EntityType("egg"),
			"ender_pearl" => new EntityType("ender_pearl"),
			"experience_bottle" => new EntityType("experience_bottle"),
			"potion" => new EntityType("potion"),
			"vex" => new EntityType("vex", 307),
			"villager" => new EntityType("villager"),
			"iron_golem" => new EntityType("iron_golem"),
			"vindicator" => new EntityType("vindicator"),
			"witch" => new EntityType("witch"),
			"wither" => new EntityType("wither"),
			"wither_skeleton" => new EntityType("wither_skeleton", 301),
			"wither_skull" => new EntityType("wither_skull"),
			"wolf" => new EntityType("wolf"),
			"zombie" => new EntityType("zombie"),
			"zombie_horse" => new EntityType("zombie_horse", 301),
			"zombie_villager" => new EntityType("zombie_villager", 301),
			"phantom" => new EntityType("phantom", 358),
			"lightning_bolt" => new EntityType("lightning_bolt"),
			"player" => new EntityType("player"),
			"fishing_bobber" => new EntityType("fishing_bobber"),
			"trident" => new EntityType("trident", 358)
		];
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	function getId(int $protocol_version)
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			foreach([
				477 => "1.14",
				393 => "1.13",
				0 => "1.12"
			] as $pv => $v)
			{
				if($protocol_version < $pv)
				{
					continue;
				}
				if(!array_key_exists($v, self::$json_cache))
				{
					self::$json_cache[$v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/entities.json"), true);
				}
				foreach(self::$json_cache[$v] as $entity)
				{
					if($entity["name"] == $this->name)
					{
						return $entity["id"];
					}
				}
			}
		}
		return null;
	}

	/**
	 * Returns the appropriate EntityMetadata class for this entity type.
	 *
	 * @return Metadata
	 */
	function getMetadata(): Metadata
	{
		switch($this->name)
		{
			case "guardian":
				return new Guardian();
			case "elder_guardian":
				return new ElderGuardian();
		}
		return new Living();
	}
}
