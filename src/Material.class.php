<?php
namespace Phpcraft;
abstract class Material extends Flattenable
{
	/**
	 * The pre-flattening metadata value accompanying the ID.
	 * @var integer $legacy_metadata
	 */
	public $legacy_metadata;

	/**
	 * @copydoc Flattenable::__construct
	 * @param integer $legacy_metadata The pre-flattening metadata value accompanying the ID.
	 */
	function __construct($name, $id, $legacy_id, $legacy_metadata)
	{
		parent::__construct($name, $id, $legacy_id);
		$this->legacy_metadata = $legacy_metadata;
	}

	/**
	 * Returns a match for the given legacy ID and accompanying metadata value, or null.
	 * @param integer $legacy_id
	 * @param integer $legacy_metadata
	 * @return Material
	 */
	static function getLegacy($legacy_id, $legacy_metadata = 0)
	{
		foreach(static::all() as $material)
		{
			if($material->legacy_id == $legacy_id && $material->legacy_metadata == $legacy_metadata)
			{
				return $material;
			}
		}
		return null;
	}
}
