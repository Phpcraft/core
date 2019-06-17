<?php
namespace Phpcraft\Nbt;
use Phpcraft\Connection;
/**
 * The base class for NBT tags.
 *
 * @see Connection::readNBT
 */
abstract class NbtTag
{
	const ORD = -1;
	/**
	 * The name of this tag.
	 *
	 * @var string $name
	 */
	public $name;

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 *
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	abstract public function write(Connection $con, bool $inList = false);

	abstract public function copy();

	abstract public function __toString();

	protected function _write(Connection $con)
	{
		assert(static::ORD != -1);
		$con->writeByte(static::ORD);
		$con->writeShort(strlen($this->name));
		$con->writeRaw($this->name);
	}
}
