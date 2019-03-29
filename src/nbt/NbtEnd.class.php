<?php
namespace Phpcraft;
class NbtEnd extends NbtTag
{
	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
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
