<?php
namespace Phpcraft;
use InvalidArgumentException;
/** A UUID helper class. */
class UUID extends \hellsh\UUID
{
	/**
	 * Returns true if the skin of a player with this UUID would be slim ("Alex" style).
	 * @return boolean
	 */
	public function isSlim()
	{
		return ((ord(substr($this->binary, 3, 1)) & 0xF) ^ (ord(substr($this->binary, 7, 1)) & 0xF) ^ (ord(substr($this->binary, 11, 1)) & 0xF) ^ (ord(substr($this->binary, 15, 1)) & 0xF)) == 1;
	}

	/**
	 * Returns an integer which will always be the same given the same UUID, but collisions are far more likely.
	 * @return integer
	 */
	public function toInt()
	{
		return gmp_intval(gmp_import(substr($this->binary, 0, 2).substr($this->binary, -2)));
	}
}
