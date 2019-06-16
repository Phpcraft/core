<?php
namespace Phpcraft;
use DomainException;
use GMP;
use hellsh\UUID;
use InvalidArgumentException;
use LengthException;
/**
 * A wrapper to read and write from streams.
 * The Connection object can also be utilized without a stream:
 * <pre>$con = new \\Phpcraft\\Connection($protocol_version);
 * $packet = new \\Phpcraft\\SpawnMobPacket();
 * // $packet->...
 * $packet->send($con);
 * echo \\Phpcraft\\Phpcraft::binaryStringToHex($con->write_buffer)."\n";</pre>
 */
class Connection
{
	/**
	 * The protocol version that is used for this connection.
	 *
	 * @var integer $protocol_version
	 */
	public $protocol_version;
	/**
	 * The stream of the connection of null.
	 *
	 * @var resource $stream
	 */
	public $stream;
	/**
	 * The amount of bytes a packet needs for it to be compressed as an integer or -1 if disabled.
	 *
	 * @var integer $compression_threshold
	 */
	public $compression_threshold = -1;
	/**
	 * The state of the connection.
	 * 1 stands for status, 2 for logging in, and 3 for playing.
	 *
	 * @var integer $state
	 */
	public $state;
	/**
	 * The write buffer binary string.
	 *
	 * @var string $write_buffer
	 */
	public $write_buffer = "";
	/**
	 * The read buffer binary string.
	 *
	 * @var string $read_buffer
	 */
	public $read_buffer = "";

	/**
	 * @param integer $protocol_version
	 * @param resource $stream
	 */
	public function __construct(int $protocol_version = -1, $stream = null)
	{
		$this->protocol_version = $protocol_version;
		if($stream)
		{
			stream_set_blocking($stream, false);
			$this->stream = $stream;
		}
	}

	/**
	 * Returns whether the stream is (still) open.
	 *
	 * @return boolean
	 */
	public function isOpen()
	{
		return $this->stream != null && @feof($this->stream) === false;
	}

	/**
	 * Closes the stream.
	 */
	public function close()
	{
		if($this->stream != null)
		{
			fclose($this->stream);
			$this->stream = null;
		}
	}

	/**
	 * Adds a byte to the write buffer.
	 *
	 * @param integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	public function writeByte(int $value, bool $signed = false)
	{
		$this->write_buffer .= pack(($signed ? "c" : "C"), $value);
		return $this;
	}

	/**
	 * Adds a boolean to the write buffer.
	 *
	 * @param boolean $value
	 * @return Connection this
	 */
	public function writeBoolean(bool $value)
	{
		$this->write_buffer .= pack("C", ($value ? 1 : 0));
		return $this;
	}

	/**
	 * Converts a number to a VarInt binary string.
	 *
	 * @param GMP|string|integer $value
	 * @return string
	 */
	public static function varInt($value)
	{
		$value = self::complimentNumber($value, 32);
		$bytes = "";
		do
		{
			$temp = gmp_intval(gmp_and($value, 0b01111111));
			$value = gmp_div($value, 128); // $value >> 7
			if(gmp_cmp($value, 0) != 0)
			{
				$temp |= 0b10000000;
			}
			$bytes .= pack("C", $temp);
		}
		while($value != 0);
		return $bytes;
	}

	/**
	 * Adds a VarInt to the write buffer.
	 *
	 * @param GMP|string|integer $value
	 * @return Connection $this
	 */
	public function writeVarInt($value)
	{
		$this->write_buffer .= self::varInt($value);
		return $this;
	}

	/**
	 * Adds a string to the write buffer.
	 *
	 * @param string $value
	 * @return Connection $this
	 */
	public function writeString(string $value)
	{
		$this->write_buffer .= self::varInt(strlen($value)).$value;
		return $this;
	}

	/**
	 * Adds a chat object to the read buffer.
	 *
	 * @param array|string $value The chat object or a strings that will be converted into a chat object.
	 * @return Connection $this
	 * @throws InvalidArgumentException
	 */
	public function writeChat($value)
	{
		if(gettype($value) == "string")
		{
			$value = Phpcraft::textToChat($value);
		}
		else if(gettype($value) != "array")
		{
			throw new InvalidArgumentException("Argument can't be of type ".gettype($value));
		}
		$this->writeString(json_encode($value));
		return null;
	}

	/**
	 * Adds the byte string to the write buffer.
	 *
	 * @param string $value
	 * @return Connection $this
	 */
	public function writeRaw(string $value)
	{
		$this->write_buffer .= $value;
		return $this;
	}

	private static function signNumber($value, int $bits)
	{
		if(gmp_cmp($value, gmp_pow(2, $bits - 1)) >= 0)
		{
			$value = gmp_sub($value, gmp_pow(2, $bits));
		}
		return $value;
	}

	private static function complimentNumber($value, int $bits)
	{
		if(gmp_cmp($value, 0) < 0)
		{
			$value = gmp_sub(gmp_pow(2, $bits), gmp_abs($value));
		}
		return $value;
	}

	private function writeGMP($value, int $bytes, bool $signed)
	{
		$bits = $bytes * 8;
		if(is_float($value))
		{
			$value = intval($value);
		}
		if($signed)
		{
			$value = self::signNumber($value, $bits);
		}
		if(gmp_cmp($value, 0) == 0)
		{
			$this->write_buffer .= str_repeat("\0", $bytes);
		}
		else
		{
			$this->write_buffer .= gmp_export(self::complimentNumber($value, $bits), $bytes, GMP_BIG_ENDIAN);
		}
		return $this;
	}

	/**
	 * Adds a short to the write buffer.
	 *
	 * @param GMP|string|integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	public function writeShort($value, bool $signed = false)
	{
		return $this->writeGMP($value, 2, $signed);
	}

	/**
	 * Adds an integer to the write buffer.
	 *
	 * @param GMP|string|integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	public function writeInt($value, bool $signed = false)
	{
		return $this->writeGMP($value, 4, $signed);
	}

	/**
	 * Adds a long to the write buffer.
	 *
	 * @param GMP|string|integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	public function writeLong($value, bool $signed = false)
	{
		return $this->writeGMP($value, 8, $signed);
	}

	/**
	 * Adds a float to the write buffer.
	 *
	 * @param float $value
	 * @return Connection $this
	 */
	public function writeFloat(float $value)
	{
		$this->write_buffer .= pack("G", $value);
		return $this;
	}

	/**
	 * Adds a double to the write buffer.
	 *
	 * @param double $value
	 * @return Connection $this
	 */
	public function writeDouble(float $value)
	{
		$this->write_buffer .= pack("E", $value);
		return $this;
	}

	/**
	 * Adds a position encoded as one long to the write buffer.
	 *
	 * @param Position $pos
	 * @return Connection $this
	 */
	public function writePosition(Position $pos)
	{
		return $this->writeLong((($pos->x & 0x3FFFFFF) << 38) | (($pos->y & 0xFFF) << 26) | ($pos->z & 0x3FFFFFF));
	}

	/**
	 * Adds a position encoded as three double to the write buffer.
	 *
	 * @param Position $pos
	 * @return Connection $this
	 */
	public function writePrecisePosition(Position $pos)
	{
		$this->writeDouble($pos->x);
		$this->writeDouble($pos->y);
		return $this->writeDouble($pos->z);
	}

	/**
	 * Adds a position encoded as three ints to the write buffer.
	 *
	 * @param Position $pos
	 * @return Connection $this
	 */
	public function writeFixedPointPosition(Position $pos)
	{
		$this->writeInt(intval($pos->x * 32));
		$this->writeInt(intval($pos->y * 32));
		return $this->writeInt(intval($pos->z * 32));
	}

	/**
	 * Adds a slot to the write buffer.
	 *
	 * @param Slot $slot
	 * @return Connection $this
	 * @throws MissingMetadataException
	 */
	public function writeSlot(Slot $slot)
	{
		if(Slot::isEmpty($slot))
		{
			if($this->protocol_version >= 402)
			{
				$this->writeBoolean(false);
			}
			else
			{
				$this->writeShort(-1);
			}
		}
		else
		{
			if($this->protocol_version >= 402)
			{
				$this->writeBoolean(true);
				$this->writeVarInt($slot->item->getId($this->protocol_version));
				$this->writeByte($slot->count);
			}
			else
			{
				$id = $slot->item->getId($this->protocol_version);
				if($this->protocol_version < 346)
				{
					$this->writeShort($id >> 4);
					$this->writeByte($slot->count);
					switch($slot->item->name)
					{
						case "filled_map":
							if(!($slot->nbt instanceof NbtCompound) || !$slot->nbt->hasChild("map"))
							{
								throw new MissingMetadataException("filled_map is missing ID.");
							}
							$this->writeShort($slot->nbt->getChild("map")->value);
							break;
						default:
							$this->writeShort($id & 0xF);
					}
				}
				else
				{
					$this->writeShort($id);
					$this->writeByte($slot->count);
				}
			}
			$nbt = $slot->getNBT();
			if($this->protocol_version < 402 && $nbt instanceof NbtCompound)
			{
				$display = $nbt->getChild("display");
				if($display && $display instanceof NbtCompound)
				{
					$name = $display->getChild("Name");
					if($name && $name instanceof NbtString)
					{
						$name->value = Phpcraft::chatToText(json_decode($name->value, true), 2);
						$nbt->addChild($display->addChild($name));
					}
				}
			}
			$nbt->write($this);
		}
		return $this;
	}

	/**
	 * Adds a UUID to the write buffer.
	 *
	 * @param UUID $uuid
	 * @return Connection $this
	 */
	public function writeUUID(UUID $uuid)
	{
		$this->write_buffer .= $uuid->binary;
		return $this;
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 *
	 * @param string|integer $packet The name or ID of the new packet.
	 * @return Connection $this
	 * @throws DomainException
	 * @throws InvalidArgumentException
	 */
	public function startPacket($packet)
	{
		if(gettype($packet) == "string")
		{
			$packetId = PacketId::get($packet);
			if(!$packetId)
			{
				throw new DomainException("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		else if(gettype($packet) != "integer")
		{
			throw new InvalidArgumentException("Packet has to be either string or integer.");
		}
		$this->write_buffer = self::varInt($packet);
		return $this;
	}

	/**
	 * Sends the contents of the write buffer over the stream and clears the write buffer or does nothing if there is no stream.
	 *
	 * @param boolean $raw When true, the write buffer is sent as-is, without length prefix or compression, which you probably don't want.
	 * @throws IOException If the connection is not open.
	 * @return Connection $this
	 */
	public function send(bool $raw = false)
	{
		if($this->stream != null)
		{
			if(@feof($this->stream) !== false)
			{
				throw new IOException("Can't send to connection that's not open.");
			}
			stream_set_blocking($this->stream, true);
			if($raw)
			{
				fwrite($this->stream, $this->write_buffer);
			}
			else
			{
				$length = strlen($this->write_buffer);
				if($this->compression_threshold > -1)
				{
					if($length >= $this->compression_threshold)
					{
						$compressed = gzcompress($this->write_buffer, 1);
						$compressed_length = strlen($compressed);
						$length_varint = self::varInt($length);
						fwrite($this->stream, self::varInt($compressed_length + strlen($length_varint)).$length_varint.$compressed) or $this->close();
					}
					else
					{
						fwrite($this->stream, self::varInt($length + 1)."\x00".$this->write_buffer) or $this->close();
					}
				}
				else
				{
					fwrite($this->stream, self::varInt($length).$this->write_buffer) or $this->close();
				}
			}
			stream_set_blocking($this->stream, false);
			$this->write_buffer = "";
		}
		return $this;
	}

	/**
	 * Puts raw bytes from the stream into the read buffer.
	 *
	 * @param float $timeout The amount of seconds to wait before the read is aborted.
	 * @param integer $bytes The exact amount of bytes you would like to receive. 0 means read up to 8 KiB.
	 * @return boolean True on success.
	 * @see Connection::readPacket
	 */
	public function readRawPacket(float $timeout = 3.000, int $bytes = 0)
	{
		$start = microtime(true);
		if($bytes == 0)
		{
			$this->read_buffer = fread($this->stream, 8192);
			while($this->read_buffer == "")
			{
				if((microtime(true) - $start) >= $timeout)
				{
					return false;
				}
				$this->read_buffer .= fread($this->stream, 8192);
			}
		}
		else
		{
			$this->read_buffer = fread($this->stream, $bytes);
			if(strlen($this->read_buffer) > 0)
			{
				$timeout += 0.1;
			}
			while(strlen($this->read_buffer) < $bytes)
			{
				if((microtime(true) - $start) >= $timeout)
				{
					return false;
				}
				$this->read_buffer .= fread($this->stream, $bytes - strlen($this->read_buffer));
			}
		}
		return strlen($this->read_buffer) > 0;
	}

	/**
	 * Puts a new packet into the read buffer.
	 *
	 * @param float $timeout The amount of seconds to wait before read is aborted.
	 * @return integer|boolean The packet's id or false if no packet was received within the time limit.
	 * @throws IOException When there are not enough bytes to read the packet ID.
	 * @throws LengthException When the packet's length or ID VarInt is too big.
	 * @see Connection::readRawPacket
	 * @see Packet::clientboundPacketIdToName()
	 * @see Packet::serverboundPacketIdToName()
	 */
	public function readPacket(float $timeout = 3.000)
	{
		$start = microtime(true);
		$length = 0;
		$read = 0;
		do
		{
			$byte = @fgetc($this->stream);
			while($byte === false)
			{
				if((microtime(true) - $start) >= $timeout)
				{
					return false;
				}
				$byte = @fgetc($this->stream);
			}
			$byte = ord($byte);
			$length |= (($byte & 0x7F) << ($read++ * 7));
			if($read > 5)
			{
				throw new LengthException("VarInt is too big");
			}
			if(($byte & 0x80) != 128)
			{
				break;
			}
		}
		while(true);
		// It's established that a packet is on the line, but it could take more than one read to get it into the read buffer, so some additional time is forcefully allocated.
		$timeout += 0.1;
		$this->read_buffer = fread($this->stream, $length);
		while(strlen($this->read_buffer) < $length)
		{
			if((microtime(true) - $start) >= $timeout)
			{
				return false;
			}
			$this->read_buffer .= fread($this->stream, $length - strlen($this->read_buffer));
		}
		if($this->compression_threshold > -1)
		{
			$uncompressed_length = gmp_intval($this->readVarInt());
			if($uncompressed_length > 0)
			{
				$this->read_buffer = @gzuncompress($this->read_buffer, $uncompressed_length);
				if(!$this->read_buffer)
				{
					return false;
				}
			}
		}
		return gmp_intval($this->readVarInt());
	}

	/**
	 * Read the specified amount of bytes from the read buffer.
	 *
	 * @param integer $bytes
	 * @return string
	 */
	public function readRaw(int $bytes)
	{
		$str = substr($this->read_buffer, 0, $bytes);
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $str;
	}

	/**
	 * Reads an integer encoded as a VarInt from the read buffer.
	 *
	 * @return GMP
	 * @throws LengthException When the VarInt is too big.
	 * @throws IOException When there are not enough bytes to read or continue reading a VarInt.
	 */
	public function readVarInt()
	{
		$value = gmp_init(0);
		$read = 0;
		do
		{
			if(strlen($this->read_buffer) == 0)
			{
				throw new LengthException("There are not enough bytes to read a VarInt.");
			}
			$byte = $this->readByte();
			$value = gmp_or($value, gmp_mul(gmp_and($byte, 0b01111111), pow(2, 7 * $read)));
			// $value |= (($byte & 0b01111111) << (7 * $read));
			if(++$read > 5)
			{
				throw new LengthException("VarInt is too big");
			}
		}
		while(($byte & 0b10000000) != 0);
		return self::signNumber($value, 32);
	}

	/**
	 * Reads a string from the read buffer.
	 *
	 * @param integer $maxLength The maxmium amount of bytes this string may use.
	 * @param integer $minLength The minimum amount of bytes this string must use.
	 * @return string
	 * @throws LengthException When the string doesn't fit the length requirements.
	 * @throws IOException When there are not enough bytes to read a string.
	 */
	public function readString(int $maxLength = 32767, int $minLength = -1)
	{
		$length = gmp_intval($this->readVarInt());
		if($length == 0)
		{
			return "";
		}
		if($length > $maxLength)
		{
			throw new IOException("The string on the wire apparently has {$length} bytes which exceeds {$maxLength}.");
		}
		if($length < $minLength)
		{
			throw new LengthException("This string on the wire apparently has {$length} bytes but at least {$minLength} are required.");
		}
		if($length > strlen($this->read_buffer))
		{
			throw new LengthException("String on the wire is apparently {$length} bytes long, but that exceeds the bytes in the read buffer.");
		}
		$str = substr($this->read_buffer, 0, $length);
		$this->read_buffer = substr($this->read_buffer, $length);
		return $str;
	}

	/**
	 * Reads a chat object from the read buffer.
	 *
	 * @return array
	 * @throws IOException When there are not enough bytes to read the string.
	 */
	public function readChat()
	{
		return json_decode($this->readString(), true);
	}

	/**
	 * Reads a byte from the read buffer.
	 *
	 * @param boolean $signed
	 * @return integer
	 * @throws IOException When there are not enough bytes to read a byte.
	 */
	public function readByte(bool $signed = false)
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new IOException("There are not enough bytes to read a byte.");
		}
		$byte = unpack(($signed ? "c" : "C")."byte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		return $byte;
	}

	/**
	 * Reads a boolean from the read buffer.
	 *
	 * @return boolean
	 * @throws IOException When there are not enough bytes to read a boolean.
	 */
	public function readBoolean()
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new IOException("There are not enough bytes to read a boolean.");
		}
		$byte = unpack("cbyte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		return $byte != 0;
	}

	/**
	 * @param integer $bytes
	 * @param bool $signed
	 * @return GMP
	 * @throws IOException
	 */
	private function readGMP(int $bytes, bool $signed)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new IOException("There are not enough bytes to read a {$bytes}-byte number.");
		}
		$value = gmp_import(substr($this->read_buffer, 0, $bytes), $bytes, GMP_BIG_ENDIAN);
		$this->read_buffer = substr($this->read_buffer, $bytes);
		$bits = $bytes * 8;
		if($signed)
		{
			$value = self::signNumber($value, $bits);
		}
		return $value;
	}

	/**
	 * Reads a short from the read buffer.
	 *
	 * @param boolean $signed
	 * @return GMP
	 * @throws IOException When there are not enough bytes to read a short.
	 */
	public function readShort(bool $signed = true)
	{
		return $this->readGMP(2, $signed);
	}

	/**
	 * Reads an integer from the read buffer.
	 *
	 * @param boolean $signed
	 * @return GMP
	 * @throws IOException When there are not enough bytes to read an integer.
	 */
	public function readInt(bool $signed = false)
	{
		return $this->readGMP(4, $signed);
	}

	/**
	 * Reads a long from the read buffer.
	 *
	 * @param boolean $signed
	 * @return GMP
	 * @throws IOException When there are not enough bytes to read a long.
	 */
	public function readLong(bool $signed = false)
	{
		return $this->readGMP(8, $signed);
	}

	/**
	 * Reads a position encoded as one long from the read buffer.
	 *
	 * @return Position
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	public function readPosition()
	{
		$val = $this->readLong();
		$pow_2_38 = gmp_pow(2, 38);
		return new Position(gmp_intval(gmp_div($val, $pow_2_38)), // $val >> 38
			gmp_intval(gmp_and(gmp_div($val, gmp_pow(2, 26)), 0xFFF)), // ($val >> 26) & 0xFFF
			gmp_intval(gmp_div(gmp_mul($val, $pow_2_38), $pow_2_38)) // $val << 38 >> 38;
		);
	}

	/**
	 * Reads a position encoded as three doubles from the read buffer.
	 *
	 * @return Position
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	public function readPrecisePosition()
	{
		return new Position($this->readDouble(), $this->readDouble(), $this->readDouble());
	}

	/**
	 * Reads a position encoded as three ints from the read buffer.
	 *
	 * @return Position
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	public function readFixedPointPosition()
	{
		return new Position(gmp_intval($this->readInt()) / 32, gmp_intval($this->readInt()) / 32, gmp_intval($this->readInt()) / 32);
	}

	/**
	 * Reads a float from the read buffer.
	 *
	 * @return float
	 * @throws IOException When there are not enough bytes to read a float.
	 */
	public function readFloat()
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new IOException("There are not enough bytes to read a float.");
		}
		$float = unpack("Gfloat", substr($this->read_buffer, 0, 4))["float"];
		$this->read_buffer = substr($this->read_buffer, 4);
		return $float;
	}

	/**
	 * Reads a double from the read buffer.
	 *
	 * @return float
	 * @throws IOException When there are not enough bytes to read a double.
	 */
	public function readDouble()
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new IOException("There are not enough bytes to read a double.");
		}
		$double = unpack("Edouble", substr($this->read_buffer, 0, 8))["double"];
		$this->read_buffer = substr($this->read_buffer, 8);
		return $double;
	}

	/**
	 * Reads a UUID.
	 *
	 * @return UUID
	 * @throws IOException When there are not enough bytes to read a UUID.
	 */
	public function readUUID()
	{
		if(strlen($this->read_buffer) < 16)
		{
			throw new IOException("There are not enough bytes to read a UUID.");
		}
		$uuid = new UUID(substr($this->read_buffer, 0, 16));
		$this->read_buffer = substr($this->read_buffer, 16);
		return $uuid;
	}

	/**
	 * Reads an NbtTag.
	 *
	 * @param int $type Ignore this parameter.
	 * @return NbtTag
	 * @throws IOException
	 * @throws DomainException
	 */
	public function readNBT(int $type = 0)
	{
		$inList = $type > 0;
		if(!$inList)
		{
			$type = $this->readByte();
		}
		$name = ($type == 0 || $inList) ? "" : $this->readRaw(gmp_intval($this->readShort()));
		switch($type)
		{
			case NbtTag::TYPE_END:
				return new NbtEnd();
			case NbtTag::TYPE_BYTE:
				return new NbtByte($name, $this->readByte(true));
			case NbtTag::TYPE_SHORT:
				return new NbtShort($name, gmp_intval($this->readShort(true)));
			case NbtTag::TYPE_INT:
				return new NbtInt($name, $this->readInt(true));
			case NbtTag::TYPE_LONG:
				return new NbtLong($name, $this->readLong(true));
			case NbtTag::TYPE_FLOAT:
				return new NbtFloat($name, $this->readFloat());
			case NbtTag::TYPE_DOUBLE:
				return new NbtDouble($name, $this->readDouble());
			case NbtTag::TYPE_BYTE_ARRAY:
				$children_i = gmp_intval($this->readInt(true));
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readByte());
				}
				return new NbtByteArray($name, $children);
			case NbtTag::TYPE_STRING:
				return new NbtString($name, $this->readRaw(gmp_intval($this->readShort())));
			case NbtTag::TYPE_LIST:
				$childType = $this->readByte();
				$children_i = gmp_intval($this->readInt(true));
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readNBT($childType));
				}
				return new NbtList($name, $childType, $children);
			case NbtTag::TYPE_COMPOUND:
				$children = [];
				while(!(($tag = $this->readNBT()) instanceof NbtEnd))
				{
					array_push($children, $tag);
				}
				return new NbtCompound($name, $children);
			case NbtTag::TYPE_INT_ARRAY:
				$children_i = gmp_intval($this->readInt(true));
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readInt(true));
				}
				return new NbtIntArray($name, $children);
			case NbtTag::TYPE_LONG_ARRAY:
				$children_i = gmp_intval($this->readInt(true));
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readLong());
				}
				return new NbtLongArray($name, $children);
			default:
				throw new DomainException("Unsupported NBT Tag: {$type}");
		}
	}

	/**
	 * Reads a Slot.
	 *
	 * @param boolean $additional_processing Whether additional processing should occur to properly receive pre-1.13 data. You should only set this to false if you want a lazy read, and don't even care about the slot.
	 * @return Slot
	 * @throws IOException
	 */
	public function readSlot(bool $additional_processing = true)
	{
		$slot = new Slot();
		if($this->protocol_version >= 402)
		{
			if(!$this->readBoolean())
			{
				return $slot;
			}
			$slot->item = Item::getById(gmp_intval($this->readVarInt()), $this->protocol_version);
			$slot->count = $this->readByte();
		}
		else
		{
			$id = gmp_intval($this->readShort());
			if($id <= 0)
			{
				return $slot;
			}
			$slot->count = $this->readByte();
			if($this->protocol_version >= 346)
			{
				$slot->item = Item::getById($id, $this->protocol_version);
			}
			else
			{
				$metadata = gmp_intval($this->readShort());
				if($additional_processing && $metadata > 0)
				{
					switch($id)
					{
						case 358:
							if(!($slot->nbt instanceof NbtCompound))
							{
								$slot->nbt = new NbtCompound("tag", []);
							}
							$addMap = true;
							$children_ = [];
							foreach($slot->nbt->children as $child)
							{
								if($child->name == "map")
								{
									if(@$child->value !== $metadata)
									{
										continue;
									}
									$addMap = false;
								}
								array_push($children_, $child);
							}
							if($addMap)
							{
								array_push($children_, new NbtInt("map", $metadata));
							}
							$slot->nbt->children = $children_;
							$metadata = 0;
							break;
					}
					$slot->item = Item::getById($id << 4 | $metadata, $this->protocol_version);
					if(!$slot->item)
					{
						$slot->item = Item::getById($id << 4, $this->protocol_version);
					}
				}
				else
				{
					$slot->item = Item::getById($id << 4, $this->protocol_version);
				}
			}
		}
		$slot->nbt = $this->readNBT();
		if($additional_processing && $this->protocol_version < 402)
		{
			if($slot->nbt instanceof NbtCompound)
			{
				$display = $slot->nbt->getChild("display");
				if($display && $display instanceof NbtCompound)
				{
					$name = $display->getChild("Name");
					if($name && $name instanceof NbtString)
					{
						$name->value = json_encode(Phpcraft::textToChat($name->value));
						$slot->nbt->addChild($display->addChild($name));
					}
				}
			}
		}
		return $slot;
	}

	/**
	 * Skips over the given amount of bytes in the read buffer.
	 *
	 * @param integer $bytes
	 * @return Connection $this
	 * @throws IOException When there are not enough bytes in the buffer to ignore the given number.
	 */
	public function ignoreBytes(int $bytes)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new IOException("There are less than {$bytes} bytes");
		}
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $this;
	}
}
