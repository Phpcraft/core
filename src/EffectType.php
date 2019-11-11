<?php
namespace Phpcraft;
class EffectType extends Identifier
{
	protected static $all_cache;
	/**
	 * The effect's ID.
	 *
	 * @var int $id
	 */
	public $id;

	function __construct(string $name, int $id, int $since_protocol_version = 0)
	{
		parent::__construct($name, $since_protocol_version);
		$this->id = $id;
	}

	/**
	 * @return void
	 */
	protected static function populateAllCache(): void
	{
		self::$all_cache = [
			"speed" => new EffectType("speed", 1),
			"slowness" => new EffectType("slowness", 2),
			"haste" => new EffectType("haste", 3),
			"mining_fatigue" => new EffectType("mining_fatigue", 4),
			"strength" => new EffectType("strength", 5),
			"instant_health" => new EffectType("instant_health", 6),
			"instant_damage" => new EffectType("instant_damage", 7),
			"jump_boost" => new EffectType("jump_boost", 8),
			"nausea" => new EffectType("nausea", 9),
			"regeneration" => new EffectType("regeneration", 10),
			"resistance" => new EffectType("resistance", 11),
			"fire_resistance" => new EffectType("fire_resistance", 12),
			"water_breathing" => new EffectType("water_breathing", 13),
			"invisibility" => new EffectType("invisibility", 14),
			"blindness" => new EffectType("blindness", 15),
			"night_vision" => new EffectType("night_vision", 16),
			"hunger" => new EffectType("hunger", 17),
			"weakness" => new EffectType("weakness", 18),
			"poison" => new EffectType("poison", 19),
			"wither" => new EffectType("wither", 20),
			"health_boost" => new EffectType("health_boost", 21),
			"absorption" => new EffectType("absorption", 22),
			"saturation" => new EffectType("saturation", 23),
			"glowing" => new EffectType("glowing", 24, 49),
			"levitation" => new EffectType("levitation", 25, 49),
			"luck" => new EffectType("luck", 26, 84),
			"unluck" => new EffectType("unluck", 27, 84),
			"slow_falling" => new EffectType("slow_falling", 28, 369),
			"conduit_power" => new EffectType("conduit_power", 29, 371),
			"dolphins_grace" => new EffectType("dolphins_grace", 30, 373),
			"bad_omen" => new EffectType("bad_omen", 31, 446),
			"hero_of_the_village" => new EffectType("hero_of_the_village", 32, 468),
		];
	}

	/**
	 * Returns the ID of this Identifier for the given protocol version or null if not applicable.
	 *
	 * @param int $protocol_version
	 * @return int|null
	 */
	function getId(int $protocol_version): ?int
	{
		return $protocol_version >= $this->since_protocol_version ? $this->id : null;
	}
}
