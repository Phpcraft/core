<?php
namespace Phpcraft;
class EntityType extends Identifier
{
	private static $json_cache = [];
	private static $all_cache;

	protected function __construct(string $name, int $since_protocol_version = 0)
	{
		parent::__construct($name, $since_protocol_version);
	}

	/**
	 * Returns every EntityType.
	 *
	 * @return EntityType[]
	 */
	static function all(): array
	{
		if(self::$all_cache === null)
		{
			self::$all_cache = [
				new EntityType("area_effect_cloud"),
				new EntityType("armor_stand"),
				new EntityType("arrow"),
				new EntityType("bat"),
				new EntityType("blaze"),
				new EntityType("boat"),
				new EntityType("cave_spider"),
				new EntityType("chicken"),
				new EntityType("cod"),
				new EntityType("cow"),
				new EntityType("creeper"),
				new EntityType("donkey"),
				new EntityType("dolphin"),
				new EntityType("dragon_fireball"),
				new EntityType("drowned"),
				new EntityType("elder_guardian"),
				new EntityType("end_crystal"),
				new EntityType("ender_dragon"),
				new EntityType("enderman"),
				new EntityType("endermite"),
				new EntityType("evoker_fangs", 307),
				new EntityType("evoker", 307),
				new EntityType("experience_orb"),
				new EntityType("eye_of_ender"),
				new EntityType("falling_block"),
				new EntityType("firework_rocket"),
				new EntityType("ghast"),
				new EntityType("giant"),
				new EntityType("guardian"),
				new EntityType("horse"),
				new EntityType("husk"),
				new EntityType("illusioner"),
				new EntityType("item"),
				new EntityType("item_frame"),
				new EntityType("fireball"),
				new EntityType("leash_knot"),
				new EntityType("llama", 307),
				new EntityType("llama_spit", 311),
				new EntityType("magma_cube"),
				new EntityType("minecart"),
				new EntityType("chest_minecart"),
				new EntityType("command_block_minecart"),
				new EntityType("furnace_minecart"),
				new EntityType("hopper_minecart"),
				new EntityType("spawner_minecart"),
				new EntityType("tnt_minecart"),
				new EntityType("mule", 301),
				new EntityType("mooshroom"),
				new EntityType("ocelot"),
				new EntityType("painting"),
				new EntityType("parrot", 318),
				new EntityType("pig"),
				new EntityType("pufferfish", 362),
				new EntityType("zombie_pigman"),
				new EntityType("polar_bear"),
				new EntityType("tnt"),
				new EntityType("rabbit"),
				new EntityType("salmon"),
				new EntityType("sheep"),
				new EntityType("shulker", 49),
				new EntityType("shulker_bullet", 49),
				new EntityType("silverfish"),
				new EntityType("skeleton"),
				new EntityType("skeleton_horse", 301),
				new EntityType("slime"),
				new EntityType("small_fireball"),
				new EntityType("snow_golem"),
				new EntityType("snowball"),
				new EntityType("spectral_arrow"),
				new EntityType("spider"),
				new EntityType("squid"),
				new EntityType("stray"),
				new EntityType("tropical_fish", 364),
				new EntityType("turtle"),
				new EntityType("egg"),
				new EntityType("ender_pearl"),
				new EntityType("experience_bottle"),
				new EntityType("potion"),
				new EntityType("vex", 307),
				new EntityType("villager"),
				new EntityType("iron_golem"),
				new EntityType("vindicator"),
				new EntityType("witch"),
				new EntityType("wither"),
				new EntityType("wither_skeleton", 301),
				new EntityType("wither_skull"),
				new EntityType("wolf"),
				new EntityType("zombie"),
				new EntityType("zombie_horse", 301),
				new EntityType("zombie_villager", 301),
				new EntityType("phantom", 358),
				new EntityType("lightning_bolt"),
				new EntityType("player"),
				new EntityType("fishing_bobber"),
				new EntityType("trident", 358)
			];
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
	 * @return EntityMetadata
	 */
	function getMetadata(): EntityMetadata
	{
		return new EntityLiving();
	}
}
