<?php
namespace Phpcraft;
abstract class Material
{
	/**
	 * The name of this material.
	 * @var string $name
	 */
	public $name;
	/**
	 * Post-flattening ID
	 * @var integer $id
	 */
	public $id;
	/**
	 * Pre-flattening ID
	 * @var integer $legacy_id
	 */
	public $legacy_id;
	/**
	 * Pre-flattening Metadata
	 * @var integer $legacy_metadata
	 */
	public $legacy_metadata;

	/**
	 * The constructor.
	 * @param string $name The name of this material.
	 * @param integer $id Post-flattening ID
	 * @param integer $legacy_id Pre-flattening ID
	 * @param integer $legacy_metadata Pre-flattening Metadata
	 */
	function __construct($name, $id, $legacy_id, $legacy_metadata)
	{
		$this->name = $name;
		$this->id = $id;
		$this->legacy_id = $legacy_id;
		$this->legacy_metadata = $legacy_metadata;
	}

	/**
	 * Returns all materials of this type.
	 */
	abstract static function all();

	/**
	 * Returns the material of this type matching the given name.
	 */
	abstract static function get($name);
}
