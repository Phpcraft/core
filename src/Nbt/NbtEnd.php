<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
class NbtEnd extends NbtTag
{
	const ORD = 0;

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 *
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	public function write(Connection $con, bool $inList = false)
	{
		$con->writeByte(0);
		return $con;
	}

	public function copy()
	{
		return new NbtEnd();
	}

	public function __toString()
	{
		return "{End}";
	}
}
