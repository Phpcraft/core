<?php
namespace Phpcraft\NBT;
use Phpcraft\Connection;
class EndTag extends NBT
{
	const ORD = 0;

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 *
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	function write(Connection $con, bool $inList = false): Connection
	{
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::write but please note that NbtEnd is not a real tag and should not be treated as such.");
		$con->writeByte(0);
		return $con;
	}

	function copy(): NBT
	{
		return new EndTag();
	}

	function __toString()
	{
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::__toString but please note that NbtEnd is not a real tag and should not be treated as such.");
		return "{End}";
	}

	/**
	 * Returns the NBT tag in SNBT (stringified NBT) format, as used in commands.
	 *
	 * @param bool $fancy
	 * @param boolean $inList Ignore this parameter.
	 * @return string
	 */
	function toSNBT(bool $fancy = false, bool $inList = false): string
	{
		trigger_error("I'm begrudgingly allowing your call to NbtEnd::toSNBT but please note that NbtEnd is not a real tag and should not be treated as such.");
		return "";
	}
}
