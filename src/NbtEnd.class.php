<?php
namespace Phpcraft;
class NbtEnd extends NbtTag
{
	/**
	 * @copydoc NbtTag::write
	 */
	function write(Connection $con, $inList = false)
	{
		$con->writeByte(0);
		return $con;
	}

	function copy()
	{
		return new NbtEnd();
	}

	function toString()
	{
		return "{End}";
	}
}
