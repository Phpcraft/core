<?php
namespace Phpcraft;
class NbtEnd extends NbtTag
{
	/**
	 * @copydoc NbtTag::write
	 */
	public function write(Connection $con, $inList = false)
	{
		$con->writeByte(0);
		return $con;
	}

	public function copy()
	{
		return new NbtEnd();
	}

	public function toString()
	{
		return "{End}";
	}
}
