<?php
namespace Phpcraft;
/**
 * Entity metadata.
 * All values are "null" by default, meaning EntityMetadata::write won't write it.
 */
abstract class EntityMetadata
{
	/**
	 * Reads metadata values from the Connection.
	 * @param Connection $con
	 * @return EntityMetadata $this
	 */
	function read(Connection $con)
	{
		do
		{
			if($con->protocol_version >= 57)
			{
				$index = $con->readByte();
				if($index == 0xFF)
				{
					break;
				}
				$type = $con->readByte();
				if(!$this->read_($con, $index))
				{
					trigger_error("Unimplemented index: {$index}");
					switch($type)
					{
						case 0:
						$con->ignoreBytes(1);
						break;

						case 1:
						$con->readVarInt();
						break;

						case 2:
						$con->ignoreBytes(4);
						break;

						case 7:
						$con->ignoreBytes(1);
						break;

						case 8:
						$con->ignoreBytes(12);
						break;

						default:
						throw new Exception("Unimplemented type: {$type}");
					}
				}
			}
			else
			{
				$type = $con->readByte();
				if($type == 0x7F)
				{
					break;
				}
				$index = $type & 0x1F;
				$type >>= 5;
				if(!$this->read_($con, $index))
				{
					trigger_error("Unimplemented legacy index: {$index}");
					switch($type)
					{
						case 0:
						$con->ignoreBytes(1);
						break;

						case 1:
						$con->ignoreBytes(2);
						break;

						case 2:
						case 3:
						$con->ignoreBytes(4);
						break;

						case 4:
						$con->ignoreBytes($con->readVarInt());
						break;

						case 5:
						$con->readSlot();
						break;

						case 6:
						case 7:
						$con->ignoreBytes(12);
						break;

						default:
						throw new Exception("Unimplemented legacy type: {$type}");
					}
				}
			}
		}
		while(true);
		return $this;
	}

	abstract protected function read_(Connection $con, $index);

	/**
	 * Writes this non-null metadata values to the Connection's write buffer.
	 * @param Connection $con
	 * @return void
	 */
	abstract function write(Connection $con);

	abstract function getStringAttributes();

	function toString()
	{
		$attr = $this->getStringAttributes();
		if(count($attr) > 0)
		{
			return "{".substr(get_called_class(), 9).": ".join(", ", $attr)."}";
		}
		return "{".substr(get_called_class(), 9)."}";
	}

	static function writeByte(Connection $con, $index, $value)
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(1);
		}
		$con->writeByte($value);
	}

	static function writeFloat(Connection $con, $index, $value)
	{
		if($con->protocol_version >= 57)
		{
			$con->writeByte($index);
			$con->writeByte(2);
		}
		else
		{
			$con->writeByte(3 << 5 | $index & 0x1F);
		}
		$con->writeFloat($value);
	}

	static function writeString(Connection $con, $index, $value)
	{
		if($con->protocol_version >= 57)
		{
			$con->writeByte($index);
			$con->writeByte(3);
		}
		else
		{
			$con->writeByte(4 << 5 | $index & 0x1F);
		}
		$con->writeString($value);
	}

	static function writeOptChat(Connection $con, $index, $value)
	{
		if($con->protocol_version < 57)
		{
			throw new Exception("OptChat is not available at this protocol version.");
		}
		$con->writeByte($index);
		$con->writeByte(5);
		if($value)
		{
			$con->writeBoolean(true);
			$con->writeChat($value);
		}
		else
		{
			$con->writeBoolean(false);
		}
	}

	static function writeBoolean(Connection $con, $index, $value)
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(5);
		}
		$con->writeBoolean($value);
	}

	static function finish(Connection $con)
	{
		if($con->protocol_version >= 57)
		{
			$con->writeByte(0xFF);
		}
		else
		{
			$con->writeByte(0x7F);
		}
	}
}
