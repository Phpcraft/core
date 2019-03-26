<?php
namespace Phpcraft;
/**
 * The base class for NBT tags.
 * @see Connection::readNBT
 */
abstract class NbtTag
{
	const TYPE_END = 0;
	const TYPE_BYTE = 1;
	const TYPE_SHORT = 2;
	const TYPE_INT = 3;
	const TYPE_LONG = 4;
	const TYPE_FLOAT = 5;
	const TYPE_DOUBLE = 6;
	const TYPE_BYTE_ARRAY = 7;
	const TYPE_STRING = 8;
	const TYPE_LIST = 9;
	const TYPE_COMPOUND = 10;
	const TYPE_INT_ARRAY = 11;
	const TYPE_LONG_ARRAY = 12;

	/**
	 * The name of this tag.
	 * @var string $name
	 */
	public $name;

	/**
	 * Adds the NBT tag to the write buffer of the connection.
	 * @param Connection $con
	 * @param boolean $inList Ignore this parameter.
	 * @return Connection $con
	 */
	abstract public function write(Connection $con, $inList = false);

	protected function _write(Connection $con, $type)
	{
		$con->writeByte($type);
		$con->writeShort(strlen($this->name));
		$con->writeRaw($this->name);
	}

	abstract public function copy();

	abstract public function toString();
}
