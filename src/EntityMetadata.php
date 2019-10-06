<?php
namespace Phpcraft;
use DomainException;
use LogicException;
use Phpcraft\Exception\IOException;
use UnexpectedValueException;
/**
 * Entity metadata.
 * All values are "null" by default, meaning EntityMetadata::write won't write it.
 */
abstract class EntityMetadata
{
	private static $fields = [];

	static function writeByte(Connection $con, int $index, int $value)
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(0);
		}
		$con->writeByte($value);
	}

	static function writeFloat(Connection $con, int $index, float $value)
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

	static function writeString(Connection $con, int $index, string $value)
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

	/**
	 * @param Connection $con
	 * @param integer $index
	 * @param array|string $value
	 * @throws LogicException
	 */
	static function writeOptChat(Connection $con, int $index, $value)
	{
		if($con->protocol_version < 57)
		{
			throw new LogicException("OptChat is not available at this protocol version.");
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

	static function writeBoolean(Connection $con, int $index, bool $value)
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

	/**
	 * Reads metadata values from the Connection.
	 *
	 * @param Connection $con
	 * @return EntityMetadata $this
	 * @throws IOException
	 */
	function read(Connection $con)
	{
		if($con->protocol_version >= 57)
		{
			$versions = [
				383 => "1.13",
				328 => "1.12",
				57 => "1.11"
			];
			do
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
					foreach($versions as $pv => $v)
					{
						if($con->protocol_version >= $pv)
						{
							if(!array_key_exists($v, self::$fields))
							{
								self::$fields[$v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/protocol.json"), true)["types"]["entityMetadataItem"][1]["fields"];
							}
							$type = self::$fields[$v][strval($type)];
							if(gettype($type) == "array")
							{
								switch($type[0])
								{
									case "option":
										if($con->readBoolean())
										{
											self::ignoreType($con, $type[1]);
										}
										break 2;
									case "container":
										foreach($type[1] as $contained)
										{
											self::ignoreType($con, $contained["type"]);
										}
										break 2;
									default:
										throw new DomainException("Unimplemented type: ".$type[0]);
								}
							}
							else
							{
								self::ignoreType($con, $type);
							}
							break;
						}
					}
				}
			}
			while(true);
		}
		else
		{
			do
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
					trigger_error("Unimplemented index: {$index}");
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
							$con->ignoreBytes(gmp_intval($con->readVarInt()));
							break;
						case 5:
							$con->readSlot();
							break;
						case 6:
						case 7:
							$con->ignoreBytes(12);
							break;
						default:
							throw new UnexpectedValueException("Invalid type: {$type}");
					}
				}
			}
			while(true);
		}
		return $this;
	}

	abstract protected function read_(Connection $con, int $index);

	/**
	 * @param Connection $con
	 * @param string $type
	 * @throws IOException
	 */
	private static function ignoreType(Connection $con, string $type)
	{
		switch($type)
		{
			case "i8":
			case "bool":
				$con->ignoreBytes(1);
				break;
			case "f32":
				$con->ignoreBytes(4);
				break;
			case "position":
				$con->ignoreBytes(8);
				break;
			case "varint":
				gmp_intval($con->readVarInt());
				break;
			case "string":
				$con->ignoreBytes(gmp_intval($con->readVarInt()));
				break;
			case "slot":
				$con->readSlot(false);
				break;
			case "nbt":
				$con->readNBT();
				break;
			default:
				throw new DomainException("Unimplemented type: {$type}");
		}
	}

	/**
	 * Writes this non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 */
	abstract function write(Connection $con);

	function __toString()
	{
		$attr = $this->getStringAttributes();
		if(count($attr) > 0)
		{
			return "{".substr(get_called_class(), 9).": ".join(", ", $attr)."}";
		}
		return "{".substr(get_called_class(), 9)."}";
	}

	abstract function getStringAttributes();
}
