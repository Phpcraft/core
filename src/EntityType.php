<?php
namespace Phpcraft;
class EntityType extends Identifier
{
	private static $all_cache;
	private $legacy_id;

	protected function __construct(string $name, int $legacy_id, int $since_protocol_version = 0)
	{
		parent::__construct($name, $since_protocol_version);
		$this->legacy_id = $legacy_id;
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
				new EntityType("area_effect_cloud", 0),
				new EntityType("armor_stand", 1),
				new EntityType("arrow", 2),
				new EntityType("bat", 3),
				new EntityType("blaze", 4),
				new EntityType("boat", 5),
				new EntityType("cave_spider", 6),
				new EntityType("chicken", 7),
				new EntityType("cod", 8),
				new EntityType("cow", 9),
				new EntityType("creeper", 10),
				new EntityType("donkey", 11),
				new EntityType("dolphin", 12),
				new EntityType("dragon_fireball", 13),
				new EntityType("drowned", 14),
				new EntityType("elder_guardian", 15),
				new EntityType("end_crystal", 16),
				new EntityType("ender_dragon", 17),
				new EntityType("enderman", 18),
				new EntityType("endermite", 19),
				new EntityType("evoker_fangs", 20, 307),
				new EntityType("evoker", 21, 307),
				new EntityType("experience_orb", 22),
				new EntityType("eye_of_ender", 23),
				new EntityType("falling_block", 24),
				new EntityType("firework_rocket", 25),
				new EntityType("ghast", 26),
				new EntityType("giant", 27),
				new EntityType("guardian", 28),
				new EntityType("horse", 29),
				new EntityType("husk", 30),
				new EntityType("illusioner", 31),
				new EntityType("item", 32),
				new EntityType("item_frame", 33),
				new EntityType("fireball", 34),
				new EntityType("leash_knot", 35),
				new EntityType("llama", 36, 307),
				new EntityType("llama_spit", 37, 311),
				new EntityType("magma_cube", 38),
				new EntityType("minecart", 39),
				new EntityType("chest_minecart", 40),
				new EntityType("command_block_minecart", 41),
				new EntityType("furnace_minecart", 42),
				new EntityType("hopper_minecart", 43),
				new EntityType("spawner_minecart", 44),
				new EntityType("tnt_minecart", 45),
				new EntityType("mule", 46, 301),
				new EntityType("mooshroom", 47),
				new EntityType("ocelot", 48),
				new EntityType("painting", 49),
				new EntityType("parrot", 50, 318),
				new EntityType("pig", 51),
				new EntityType("pufferfish", 52, 362),
				new EntityType("zombie_pigman", 53),
				new EntityType("polar_bear", 54),
				new EntityType("tnt", 55),
				new EntityType("rabbit", 56),
				new EntityType("salmon", 57),
				new EntityType("sheep", 58),
				new EntityType("shulker", 59, 49),
				new EntityType("shulker_bullet", 60, 49),
				new EntityType("silverfish", 61),
				new EntityType("skeleton", 62),
				new EntityType("skeleton_horse", 63, 301),
				new EntityType("slime", 64),
				new EntityType("small_fireball", 65),
				new EntityType("snow_golem", 66),
				new EntityType("snowball", 67),
				new EntityType("spectral_arrow", 68),
				new EntityType("spider", 69),
				new EntityType("squid", 70),
				new EntityType("stray", 71),
				new EntityType("tropical_fish", 72, 364),
				new EntityType("turtle", 73),
				new EntityType("egg", 74),
				new EntityType("ender_pearl", 75),
				new EntityType("experience_bottle", 76),
				new EntityType("potion", 77),
				new EntityType("vex", 78, 307),
				new EntityType("villager", 79),
				new EntityType("iron_golem", 80),
				new EntityType("vindicator", 81),
				new EntityType("witch", 82),
				new EntityType("wither", 83),
				new EntityType("wither_skeleton", 84, 301),
				new EntityType("wither_skull", 85),
				new EntityType("wolf", 86),
				new EntityType("zombie", 87),
				new EntityType("zombie_horse", 88, 301),
				new EntityType("zombie_villager", 89, 301),
				new EntityType("phantom", 90, 358),
				new EntityType("lightning_bolt", 91),
				new EntityType("player", 92),
				new EntityType("fishing_bobber", 93),
				new EntityType("trident", 94, 358)
			];
		}
		return self::$all_cache;
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param integer $protocol_version
	 * @return integer
	 */
	function getId(int $protocol_version): int
	{
		if($protocol_version >= $this->since_protocol_version)
		{
			if($protocol_version >= 353)
			{
				foreach(Phpcraft::getCachableJson("https://raw.githubusercontent.com/PrismarineJS/minecraft-data/master/data/pc/1.13/entities.json") as $entity)
				{
					if($entity["name"] == $this->name)
					{
						return $entity["id"];
					}
				}
			}
			else
			{
				return $this->legacy_id;
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
