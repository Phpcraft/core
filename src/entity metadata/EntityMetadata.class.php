<?php
namespace Phpcraft;
/**
 * Entity metadata.
 * All values are "null" by default, meaning EntityMetadata::write won't write it.
 */
abstract class EntityMetadata
{
	/**
	 * @param Connection $con
	 * @param string $type
	 * @throws Exception
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
			$con->readVarInt();
			break;

			case "string":
			$con->ignoreBytes($con->readVarInt());
			break;

			case "slot":
			$con->readSlot(false);
			break;

			case "nbt":
			$con->readNBT();
			break;

			default:
			throw new Exception("Unimplemented type: {$type}");
		}
	}

	/**
	 * Reads metadata values from the Connection.
	 * @param Connection $con
	 * @return EntityMetadata $this
	 * @throws Exception
	 */
	public function read(Connection $con)
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
							$type = Phpcraft::getCachableJson("https://raw.githubusercontent.com/timmyrs/minecraft-data/master/data/pc/{$v}/protocol.json")["types"]["entityMetadataItem"][1]["fields"][$type];
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
									throw new Exception("Unimplemented type: ".$type[0]);
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
						throw new Exception("Invalid type: {$type}");
					}
				}
			}
			while(true);
		}
		return $this;
	}

	abstract protected function read_(Connection $con, int $index);

	/**
	 * Writes this non-null metadata values to the Connection's write buffer.
	 * @param Connection $con
	 */
	abstract public function write(Connection $con);

	abstract public function getStringAttributes();

	public function __toString()
	{
		$attr = $this->getStringAttributes();
		if(count($attr) > 0)
		{
			return "{".substr(get_called_class(), 9).": ".join(", ", $attr)."}";
		}
		return "{".substr(get_called_class(), 9)."}";
	}

	public static function writeByte(Connection $con, int $index, int $value)
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(1);
		}
		$con->writeByte($value);
	}

	public static function writeFloat(Connection $con, int $index, float $value)
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

	public static function writeString(Connection $con, int $index, string $value)
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
	 * @throws Exception
	 */
	public static function writeOptChat(Connection $con, int $index, $value)
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

	public static function writeBoolean(Connection $con, int $index, bool $value)
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(5);
		}
		$con->writeBoolean($value);
	}

	public static function finish(Connection $con)
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
