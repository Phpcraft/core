<?php
namespace Phpcraft\Entity;
use DomainException;
use GMP;
use LogicException;
use Phpcraft\
{Connection, Exception\IOException, Phpcraft};
use RuntimeException;
use UnexpectedValueException;
/**
 * Entity metadata.
 * All values are "null" by default, meaning EntityMetadata::write won't write it.
 */
abstract class Metadata
{
	private static $fields = [];

	/**
	 * @param Connection $con
	 * @param int $index
	 * @param int $value
	 * @return void
	 */
	static function writeByte(Connection $con, int $index, int $value): void
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			$con->writeByte(0);
		}
		$con->writeByte($value);
	}

	/**
	 * @param Connection $con
	 * @param int $index
	 * @param float $value
	 * @return void
	 */
	static function writeFloat(Connection $con, int $index, float $value): void
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

	/**
	 * @param Connection $con
	 * @param int $index
	 * @param string $value
	 * @return void
	 */
	static function writeString(Connection $con, int $index, string $value): void
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
	 * @param int $index
	 * @param array|string|null $value
	 * @return void
	 * @throws LogicException
	 */
	static function writeOptChat(Connection $con, int $index, $value): void
	{
		if($con->protocol_version < 57)
		{
			throw new LogicException("OptChat is not available at this protocol version");
		}
		$con->writeByte($index);
		$con->writeByte(5);
		if($value !== null)
		{
			$con->writeBoolean(true);
			$con->writeChat($value);
		}
		else
		{
			$con->writeBoolean(false);
		}
	}

	/**
	 * @param Connection $con
	 * @param int $index
	 * @param bool $value
	 * @return void
	 */
	static function writeBoolean(Connection $con, int $index, bool $value): void
	{
		$con->writeByte($index);
		if($con->protocol_version >= 57)
		{
			self::writeType($con, "bool");
		}
		$con->writeBoolean($value);
	}

	/**
	 * @param Connection $con
	 * @param string $type
	 * @return void
	 */
	private static function writeType(Connection $con, string $type): void
	{
		$versions = [
			472 => "1.14",
			383 => "1.13",
			328 => "1.12",
			57 => "1.11"
		];
		foreach($versions as $pv => $v)
		{
			if($con->protocol_version >= $pv)
			{
				if(!array_key_exists($v, self::$fields))
				{
					self::$fields[$v] = json_decode(file_get_contents(Phpcraft::DATA_DIR."/minecraft-data/{$v}/protocol.json"), true)["types"]["entityMetadataItem"][1]["fields"];
				}
				foreach(self::$fields[$v] as $id => $_type)
				{
					if($_type == $type)
					{
						$con->writeByte($id);
						return;
					}
				}
				break;
			}
		}
		throw new RuntimeException("Unable to write type id for type $type");
	}

	/**
	 * @param Connection $con
	 * @param int $index
	 * @param GMP|string|int $value
	 * @return void
	 */
	static function writeInt(Connection $con, int $index, $value): void
	{
		if($con->protocol_version >= 57)
		{
			$con->writeByte($index);
			$con->writeByte(1);
			$con->writeVarInt($value);
		}
		else
		{
			$con->writeByte(2 << 5 | $index & 0x1F);
			$con->writeInt($value);
		}
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function finish(Connection $con): void
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
	 * @return Metadata $this
	 * @throws IOException
	 */
	function read(Connection $con): Metadata
	{
		if($con->protocol_version >= 57)
		{
			$versions = [
				472 => "1.14",
				383 => "1.13",
				328 => "1.12",
				57 => "1.11"
			];
			do
			{
				$index = $con->readUnsignedByte();
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

	/**
	 * @param Connection $con
	 * @param int $index
	 * @return bool
	 */
	abstract protected function read_(Connection $con, int $index): bool;

	/**
	 * @param Connection $con
	 * @param string $type
	 * @throws IOException
	 */
	private static function ignoreType(Connection $con, string $type): void
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
	 * Writes non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 * @return void
	 */
	abstract function write(Connection $con): void;

	function __toString()
	{
		$attr = $this->getStringAttributes();
		if(count($attr) > 0)
		{
			return "{".substr(get_called_class(), 9).": ".join(", ", $attr)."}";
		}
		return "{".substr(get_called_class(), 9)."}";
	}

	/**
	 * @return array<string>
	 */
	abstract function getStringAttributes(): array;
}
