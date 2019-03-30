<?php
namespace Phpcraft;
abstract class Gamemode
{
	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;

	/**
	 * Returns true if the given integer is a valid gamemode.
	 * @return boolean
	 */
	public static function validate(int $gamemode)
	{
		return $gamemode >= 0 && $gamemode <= 3;
	}
}
