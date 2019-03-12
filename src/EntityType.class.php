<?php
namespace Phpcraft;
class EntityType extends Flattenable
{
	/**
	 * @copydoc Flattenable::all
	 */
	static function all()
	{
		return [
			new EntityType("ender_dragon",14,63),
			new EntityType("wither",76,64)
		];
	}

	/**
	 * Returns a match for the given legacy ID or null.
	 * @param integer $legacy_id
	 * @return EntityType
	 */
	static function getLegacy($legacy_id)
	{
		foreach(EntityType::all() as $entityType)
		{
			if($entityType->legacy_id == $legacy_id)
			{
				return $entityType;
			}
		}
		return null;
	}

	/**
	 * Returns the appropriate EntityMetadata class for this entity type.
	 * @return EntityMetadata 
	 */
	function getMetadata()
	{
		return new \Phpcraft\EntityLiving();
	}
}
