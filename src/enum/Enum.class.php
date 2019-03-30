<?php
namespace Phpcraft;
abstract class Enum
{
	/**
	 * Returns an array with all keys and their values of this enum.
	 * @return array
	 */
	public static function all()
	{
		return (new \ReflectionClass(get_called_class()))->getConstants();
	}

	/**
	 * Returns true if this enum has a constant with the given name.
	 * @param string $name
	 * @return boolean
	 */
	public static function validateName(string $name)
	{
		return array_key_exists($name, static::all());
	}

	/**
	 * Returns true if this enum has a constant with the given value.
	 * @param mixed $value
	 * @return boolean
	 */
	public static function validateValue($value)
	{
		return in_array($value, static::all());
	}
}
