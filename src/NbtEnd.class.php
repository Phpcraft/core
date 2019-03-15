<?php
namespace Phpcraft;
class NbtEnd extends NbtTag
{
	/**
	 * @copydoc NbtTag::send
	 */
	function send(Connection $con, $inList = false)
	{
		$con->writeByte(0);
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
