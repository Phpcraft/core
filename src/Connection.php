<?php
namespace Phpcraft;
use DomainException;
use GMP;
use hellsh\UUID;
use InvalidArgumentException;
use LengthException;
use Phpcraft\Exception\
{IOException, MissingMetadataException};
use Phpcraft\NBT\
{ByteArrayTag, ByteTag, CompoundTag, DoubleTag, EndTag, FloatTag, IntArrayTag, IntTag, ListTag, LongArrayTag, LongTag, NBT, ShortTag, StringTag};
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
	static $zero;
	static $pow2 = [];
	/**
	 * The protocol version that is used for this connection.
	 *
	 * @var int $protocol_version
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
	 * @var int $compression_threshold
	 */
	public $compression_threshold = -1;
	/**
	 * The state of the connection.
	 * 1 stands for status, 2 for logging in, and 3 for playing.
	 *
	 * @var int $state
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
	 * @see Connection::setReadBuffer()
	 */
	public $read_buffer = "";
	public $read_buffer_offset = 0;

	/**
	 * @param int $protocol_version
	 * @param resource $stream
	 */
	function __construct(int $protocol_version = -1, $stream = null)
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
	function isOpen(): bool
	{
		return $this->stream != null && @feof($this->stream) === false;
	}

	/**
	 * Updates the read buffer correctly.
	 *
	 * @param string $buffer New read buffer binary string
	 */
	function setReadBuffer(string $buffer)
	{
		$this->read_buffer = $buffer;
		$this->read_buffer_offset = 0;
	}

	/**
	 * Returns all the data in the read buffer that is yet to be read.
	 *
	 * @return string
	 */
	function getRemainingData(): string
	{
		return substr($this->read_buffer, $this->read_buffer_offset);
	}

	/**
	 * Adds a chat object to the read buffer.
	 *
	 * @param array|string $value The chat object or a strings that will be converted into a chat object.
	 * @return Connection $this
	 */
	function writeChat($value): Connection
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
		return $this;
	}

	/**
	 * Adds a string to the write buffer.
	 *
	 * @param string $value
	 * @return Connection $this
	 */
	function writeString(string $value): Connection
	{
		$this->write_buffer .= self::varInt(strlen($value)).$value;
		return $this;
	}

	/**
	 * Converts a number to a VarInt binary string.
	 *
	 * @param GMP|int|string $value
	 * @return string
	 */
	static function varInt($value): string
	{
		if(is_float($value))
		{
			$value = intval($value);
		}
		if(gmp_cmp($value, 0) < 0)
		{
			$value = gmp_sub(self::$pow2[32], gmp_abs($value));
		}
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
	 * Adds the byte string to the write buffer.
	 *
	 * @param string $value
	 * @return Connection $this
	 */
	function writeRaw(string $value): Connection
	{
		$this->write_buffer .= $value;
		return $this;
	}

	/**
	 * Adds a float to the write buffer.
	 *
	 * @param float $value
	 * @return Connection $this
	 */
	function writeFloat(float $value): Connection
	{
		$this->write_buffer .= pack("G", $value);
		return $this;
	}

	/**
	 * Adds a position encoded as an unsigned long to the write buffer.
	 *
	 * @param Point3D $pos
	 * @return Connection $this
	 */
	function writePosition(Point3D $pos): Connection
	{
		$long = gmp_mul(gmp_and(intval($pos->x), 0x3FFFFFF), self::$pow2[38]); // $long = ($pos->x & 0x3FFFFFF) << 38;
		if($this->protocol_version < 472)
		{
			$long = gmp_or($long, gmp_mul(gmp_and(intval($pos->y), 0xFFF), self::$pow2[26])); // $long |= ($pos->y & 0xFFF) << 26;
			$long = gmp_or($long, gmp_and(intval($pos->z), 0x3FFFFFF)); // $long |= ($pos->z & 0x3FFFFFF);
		}
		else
		{
			$long = gmp_or($long, gmp_mul(gmp_and(intval($pos->z), 0x3FFFFFF), self::$pow2[12])); // $long |= ($pos->z & 0x3FFFFFF) << 12;
			$long = gmp_or($long, gmp_and(intval($pos->y), 0xFFF)); // $long |= ($pos->y & 0xFFF);
		}
		$this->writeGMP($long, 8, 64, false);
		return $this;
	}

	private function writeGMP($value, int $bytes, int $bits, bool $signed)
	{
		if(is_float($value))
		{
			$value = intval($value);
		}
		if($signed)
		{
			$value = self::signNumber($value, $bits);
		}
		$cmp0 = gmp_cmp($value, 0);
		if($cmp0 == 0)
		{
			$this->write_buffer .= str_repeat("\0", $bytes);
		}
		else
		{
			if($cmp0 < 0)
			{
				$value = gmp_sub(self::$pow2[$bits], gmp_abs($value));
			}
			$this->write_buffer .= gmp_export($value, $bytes, GMP_BIG_ENDIAN);
		}
	}

	private static function signNumber($value, int $bits)
	{
		if(gmp_cmp($value, self::$pow2[$bits - 1]) >= 0)
		{
			$value = gmp_sub($value, self::$pow2[$bits]);
		}
		return $value;
	}

	/**
	 * Adds a signed long to the write buffer.
	 *
	 * @param GMP|int|string $value
	 * @return Connection $this
	 */
	function writeLong($value): Connection
	{
		$this->writeGMP($value, 8, 64, true);
		return $this;
	}

	/**
	 * Adds a position encoded as three double to the write buffer.
	 *
	 * @param Point3D $pos
	 * @return Connection $this
	 */
	function writePrecisePosition(Point3D $pos): Connection
	{
		$this->writeDouble($pos->x);
		$this->writeDouble($pos->y);
		return $this->writeDouble($pos->z);
	}

	/**
	 * Adds a double to the write buffer.
	 *
	 * @param double $value
	 * @return Connection $this
	 */
	function writeDouble(float $value): Connection
	{
		$this->write_buffer .= pack("E", $value);
		return $this;
	}

	/**
	 * Adds a position encoded as three ints to the write buffer.
	 *
	 * @param Point3D $pos
	 * @return Connection $this
	 */
	function writeFixedPointPosition(Point3D $pos): Connection
	{
		$this->writeInt(intval($pos->x * 32));
		$this->writeInt(intval($pos->y * 32));
		return $this->writeInt(intval($pos->z * 32));
	}

	/**
	 * Adds a signed integer to the write buffer.
	 *
	 * @param GMP|int|string $value
	 * @return Connection $this
	 */
	function writeInt($value): Connection
	{
		$this->writeGMP($value, 4, 32, true);
		return $this;
	}

	/**
	 * Adds a slot to the write buffer.
	 *
	 * @param Slot $slot
	 * @return Connection $this
	 * @throws MissingMetadataException
	 */
	function writeSlot(Slot $slot): Connection
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
							if(!($slot->nbt instanceof CompoundTag) || !$slot->nbt->hasChild("map"))
							{
								throw new MissingMetadataException("filled_map is missing ID");
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
			if($this->protocol_version < 402 && $slot->nbt instanceof CompoundTag)
			{
				$display = $slot->nbt->getChild("display");
				if($display && $display instanceof CompoundTag)
				{
					$name = $display->getChild("Name");
					if($name && $name instanceof StringTag)
					{
						$name->value = Phpcraft::chatToText(json_decode($name->value, true), 2);
					}
				}
			}
			$slot->nbt->write($this);
		}
		return $this;
	}

	/**
	 * Adds a boolean to the write buffer.
	 *
	 * @param boolean $value
	 * @return Connection this
	 */
	function writeBoolean(bool $value): Connection
	{
		$this->write_buffer .= pack("C", ($value ? 1 : 0));
		return $this;
	}

	/**
	 * Adds a signed short to the write buffer.
	 *
	 * @param GMP|int|string $value
	 * @return Connection $this
	 */
	function writeShort($value): Connection
	{
		$this->writeGMP($value, 2, 16, true);
		return $this;
	}

	/**
	 * Adds a VarInt to the write buffer.
	 *
	 * @param GMP|int|string $value
	 * @return Connection $this
	 */
	function writeVarInt($value): Connection
	{
		$this->write_buffer .= self::varInt($value);
		return $this;
	}

	/**
	 * Adds a signed byte to the write buffer.
	 *
	 * @param int $value
	 * @return Connection $this
	 */
	function writeByte(int $value): Connection
	{
		$this->write_buffer .= pack("c", $value);
		return $this;
	}

	/**
	 * Adds an unsigned short to the write buffer.
	 *
	 * @param GMP|int|string $value
	 * @return Connection $this
	 */
	function writeUnsignedShort($value): Connection
	{
		$this->writeGMP($value, 2, 16, false);
		return $this;
	}

	/**
	 * Adds an unsigned byte to the write buffer.
	 *
	 * @param int $value
	 * @return Connection $this
	 */
	function writeUnsignedByte(int $value): Connection
	{
		$this->write_buffer .= pack("C", $value);
		return $this;
	}

	/**
	 * Adds an angle to the write buffer.
	 *
	 * @param float $value
	 * @return Connection $this
	 */
	function writeAngle(float $value): Connection
	{
		return $this->writeByte($value / 360 * 256);
	}

	/**
	 * Adds a UUID to the write buffer.
	 *
	 * @param UUID $uuid
	 * @return Connection $this
	 */
	function writeUUID(UUID $uuid): Connection
	{
		$this->write_buffer .= $uuid->binary;
		return $this;
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 *
	 * @param string|integer|PacketId $packet The name or ID of the new packet.
	 * @return Connection $this
	 */
	function startPacket($packet): Connection
	{
		if($packet instanceof PacketId)
		{
			$packet = $packet->getId($this->protocol_version);
		}
		else if(gettype($packet) == "string")
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
			throw new InvalidArgumentException("Packet has to be either string or integer");
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
	function send(bool $raw = false): Connection
	{
		if($this->stream != null)
		{
			if(@feof($this->stream) !== false)
			{
				throw new IOException("Can't send to connection that's not open");
			}
			stream_set_blocking($this->stream, true);
			if($raw)
			{
				$w = @fwrite($this->stream, $this->write_buffer);
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
						$w = @fwrite($this->stream, self::varInt($compressed_length + strlen($length_varint)).$length_varint.$compressed) or $this->close();
					}
					else
					{
						$w = @fwrite($this->stream, self::varInt($length + 1)."\x00".$this->write_buffer) or $this->close();
					}
				}
				else
				{
					$w = @fwrite($this->stream, self::varInt($length).$this->write_buffer) or $this->close();
				}
			}
			stream_set_blocking($this->stream, false);
			$this->write_buffer = "";
			if(!$w)
			{
				$this->close();
			}
		}
		return $this;
	}

	/**
	 * Closes the stream.
	 */
	function close()
	{
		if($this->stream != null)
		{
			stream_socket_shutdown($this->stream, STREAM_SHUT_RDWR);
			fclose($this->stream);
			$this->stream = null;
		}
	}

	/**
	 * Puts raw bytes from the stream into the read buffer.
	 *
	 * @param float $timeout The amount of seconds to wait before the read is aborted.
	 * @param int $bytes The exact amount of bytes you would like to receive. 0 means read up to 8 KiB.
	 * @return boolean True on success.
	 * @see Connection::readPacket
	 */
	function readRawPacket(float $timeout = 3.000, int $bytes = 0): bool
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
			$this->read_buffer = @fread($this->stream, $bytes);
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
		$this->read_buffer_offset = 0;
		return strlen($this->read_buffer) > 0;
	}

	/**
	 * Puts a new packet into the read buffer.
	 *
	 * @param float $timeout The amount of seconds to wait before read is aborted.
	 * @return int|boolean The packet's ID or false if no packet was received within the time limit.
	 * @throws IOException
	 * @see Connection::readRawPacket
	 * @see Packet::clientboundPacketIdToName()
	 * @see Packet::serverboundPacketIdToName()
	 */
	function readPacket(float $timeout = 3.000)
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
			$length |= (($byte & 0b01111111) << (7 * $read++));
			if($length > 2097152)
			{
				throw new IOException("Packet length wider than 21 bits");
			}
		}
		while(($byte & 0b10000000) != 0);
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
			$uncompressed_length = 0;
			$offset = 0;
			do
			{
				$byte = unpack("Cbyte", substr($this->read_buffer, $offset, 1))["byte"];
				$uncompressed_length |= (($byte & 0b01111111) << (7 * $offset++));
				if($uncompressed_length > 2097152)
				{
					throw new IOException("Uncompressed packet length wider than 21 bits");
				}
			}
			while(($byte & 0b10000000) != 0);
			$this->read_buffer = substr($this->read_buffer, $offset);
			if($uncompressed_length > 0)
			{
				$this->read_buffer = @gzuncompress($this->read_buffer, $uncompressed_length);
				if(!$this->read_buffer)
				{
					throw new IOException("Failed to decompress the packet data");
				}
			}
		}
		$this->read_buffer_offset = 0;
		return gmp_intval($this->readVarInt());
	}

	/**
	 * Reads an integer encoded as a VarInt from the read buffer.
	 *
	 * @return GMP
	 * @throws IOException When the VarInt is too big or there are not enough bytes to read or continue reading a VarInt
	 */
	function readVarInt(): GMP
	{
		$value = self::$zero;
		$read = 0;
		do
		{
			if($this->read_buffer_offset >= strlen($this->read_buffer))
			{
				throw new IOException("There are not enough bytes to read a VarInt");
			}
			$byte = unpack("Cbyte", substr($this->read_buffer, $this->read_buffer_offset++, 1))["byte"];
			$value = gmp_or($value, gmp_mul(gmp_and($byte, 0b01111111), pow(2, 7 * $read)));
			// $value |= (($byte & 0b01111111) << (7 * $read));
			if(++$read > 5)
			{
				throw new IOException("VarInt is too big");
			}
		}
		while(($byte & 0b10000000) != 0);
		return self::signNumber($value, 32);
	}

	/**
	 * Reads an unsigned byte from the read buffer.
	 *
	 * @return int
	 * @throws IOException When there are not enough bytes to read a byte.
	 */
	function readUnsignedByte(): int
	{
		if($this->read_buffer_offset >= strlen($this->read_buffer))
		{
			throw new IOException("There are not enough bytes to read a byte");
		}
		return unpack("Cbyte", substr($this->read_buffer, $this->read_buffer_offset++, 1))["byte"];
	}

	/**
	 * Reads an angle from the read buffer.
	 *
	 * @return float
	 * @throws IOException When there are not enough bytes to read an angle.
	 */
	function readAngle(): float
	{
		return $this->readByte() / 256 * 360;
	}

	/**
	 * Reads a signed byte from the read buffer.
	 *
	 * @return int
	 * @throws IOException When there are not enough bytes to read a byte.
	 */
	function readByte(): int
	{
		if($this->read_buffer_offset >= strlen($this->read_buffer))
		{
			throw new IOException("There are not enough bytes to read a byte");
		}
		return unpack("cbyte", substr($this->read_buffer, $this->read_buffer_offset++, 1))["byte"];
	}

	/**
	 * Reads a chat object from the read buffer.
	 *
	 * @return array
	 * @throws IOException When there are not enough bytes to read the string.
	 */
	function readChat(): array
	{
		return json_decode($this->readString(), true);
	}

	/**
	 * Reads a string from the read buffer.
	 *
	 * @param int $maxLength The maxmium amount of bytes this string may use.
	 * @param int $minLength The minimum amount of bytes this string must use.
	 * @return string
	 * @throws LengthException When the string doesn't fit the length requirements.
	 * @throws IOException When there are not enough bytes to read a string.
	 */
	function readString(int $maxLength = 32767, int $minLength = -1): string
	{
		$length = gmp_intval($this->readVarInt());
		if($length == 0)
		{
			return "";
		}
		if($length > $maxLength)
		{
			throw new IOException("The string on the wire apparently has {$length} bytes which exceeds {$maxLength}");
		}
		if($length < $minLength)
		{
			throw new LengthException("This string on the wire apparently has {$length} bytes but at least {$minLength} are required");
		}
		if($length > strlen($this->read_buffer))
		{
			throw new LengthException("String on the wire is apparently {$length} bytes long, but that exceeds the bytes in the read buffer");
		}
		$str = substr($this->read_buffer, $this->read_buffer_offset, $length);
		$this->read_buffer_offset += $length;
		return $str;
	}

	/**
	 * Reads a position encoded as an unsigned long from the read buffer.
	 *
	 * @return Point3D
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	function readPosition(): Point3D
	{
		$long = $this->readGMP(8, 64, false);
		$x = gmp_div($long, self::$pow2[38]); // $long >> 38
		if($this->protocol_version < 472)
		{
			$y = gmp_and(gmp_div($long, self::$pow2[26]), 0xFFF); // ($long >> 26) & 0xFFF
			$z = gmp_and($long, 0x3FFFFFF);
		}
		else
		{
			$y = gmp_and($long, 0xFFF); // $long & 0xFFF
			$z = gmp_and(gmp_div($long, self::$pow2[12]), 0x3FFFFFF); // ($long >> 12) & 0x3FFFFFF
		}
		return new Point3D(gmp_intval(self::signNumber($x, 26)), gmp_intval(self::signNumber($y, 12)), gmp_intval(self::signNumber($z, 26)));
	}

	/**
	 * @param int $bytes
	 * @param int $bits
	 * @param bool $signed
	 * @return GMP
	 * @throws IOException
	 */
	private function readGMP(int $bytes, int $bits, bool $signed): GMP
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < $bytes)
		{
			throw new IOException("There are not enough bytes to read a {$bytes}-byte number");
		}
		$value = gmp_import(substr($this->read_buffer, $this->read_buffer_offset, $bytes), $bytes, GMP_BIG_ENDIAN);
		$this->read_buffer_offset += $bytes;
		if($signed)
		{
			$value = self::signNumber($value, $bits);
		}
		return $value;
	}

	/**
	 * Reads a position encoded as three doubles from the read buffer.
	 *
	 * @return Point3D
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	function readPrecisePosition(): Point3D
	{
		return new Point3D($this->readDouble(), $this->readDouble(), $this->readDouble());
	}

	/**
	 * Reads a double from the read buffer.
	 *
	 * @return float
	 * @throws IOException When there are not enough bytes to read a double.
	 */
	function readDouble(): float
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < 8)
		{
			throw new IOException("There are not enough bytes to read a double");
		}
		$double = unpack("Edouble", substr($this->read_buffer, $this->read_buffer_offset, 8))["double"];
		$this->read_buffer_offset += 8;
		return $double;
	}

	/**
	 * Reads a position encoded as three ints from the read buffer.
	 *
	 * @return Point3D
	 * @throws IOException When there are not enough bytes to read a position.
	 */
	function readFixedPointPosition(): Point3D
	{
		return new Point3D(gmp_intval($this->readInt()) / 32, gmp_intval($this->readInt()) / 32, gmp_intval($this->readInt()) / 32);
	}

	/**
	 * Reads a signed integer from the read buffer.
	 *
	 * @return GMP
	 * @throws IOException When there are not enough bytes to read an integer.
	 */
	function readInt(): GMP
	{
		return $this->readGMP(4, 32, true);
	}

	/**
	 * Reads a UUID.
	 *
	 * @return UUID
	 * @throws IOException When there are not enough bytes to read a UUID.
	 */
	function readUUID(): UUID
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < 16)
		{
			throw new IOException("There are not enough bytes to read a UUID");
		}
		$uuid = new UUID(substr($this->read_buffer, $this->read_buffer_offset, 16));
		$this->read_buffer_offset += 16;
		return $uuid;
	}

	/**
	 * Reads a Slot.
	 *
	 * @param boolean $additional_processing Whether additional processing should occur to properly receive pre-1.13 data. You should only set this to false if you want a lazy read, and don't even care about the slot.
	 * @return Slot
	 * @throws IOException
	 */
	function readSlot(bool $additional_processing = true): Slot
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
			$id = $this->readShort();
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
				$metadata = $this->readShort();
				if($additional_processing && $metadata > 0)
				{
					switch($id)
					{
						case 358:
							if(!($slot->nbt instanceof CompoundTag))
							{
								$slot->nbt = new CompoundTag("tag", []);
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
								array_push($children_, new IntTag("map", $metadata));
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
			if($slot->nbt instanceof CompoundTag)
			{
				$display = $slot->nbt->getChild("display");
				if($display && $display instanceof CompoundTag)
				{
					$name = $display->getChild("Name");
					if($name && $name instanceof StringTag)
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
	 * Reads a boolean from the read buffer.
	 *
	 * @return boolean
	 * @throws IOException When there are not enough bytes to read a boolean.
	 */
	function readBoolean(): bool
	{
		if($this->read_buffer_offset >= strlen($this->read_buffer))
		{
			throw new IOException("There are not enough bytes to read a boolean");
		}
		$byte = unpack("Cbyte", substr($this->read_buffer, $this->read_buffer_offset++, 1))["byte"];
		if($byte > 1)
		{
			throw new IOException("Invalid boolean value: $byte");
		}
		return $byte != 0;
	}

	/**
	 * Reads a signed short from the read buffer.
	 *
	 * @return int
	 * @throws IOException When there are not enough bytes to read a short.
	 */
	function readShort(): int
	{
		return gmp_intval($this->readGMP(2, 16, true));
	}

	/**
	 * Reads NBT data.
	 *
	 * @param int $type Ignore this parameter.
	 * @return NBT
	 * @throws IOException
	 */
	function readNBT(int $type = 0): NBT
	{
		$inList = $type > 0;
		if(!$inList)
		{
			$type = $this->readByte();
		}
		$name = ($type == 0 || $inList) ? "" : $this->readRaw($this->readShort());
		switch($type)
		{
			case EndTag::ORD:
				return new EndTag();
			case ByteTag::ORD:
				return new ByteTag($name, $this->readByte());
			case ShortTag::ORD:
				return new ShortTag($name, $this->readShort());
			case IntTag::ORD:
				return new IntTag($name, $this->readInt());
			case LongTag::ORD:
				return new LongTag($name, $this->readLong());
			case FloatTag::ORD:
				return new FloatTag($name, $this->readFloat());
			case DoubleTag::ORD:
				return new DoubleTag($name, $this->readDouble());
			case ByteArrayTag::ORD:
				$children_i = gmp_intval($this->readInt());
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readByte());
				}
				return new ByteArrayTag($name, $children);
			case StringTag::ORD:
				return new StringTag($name, $this->readRaw($this->readShort()));
			case ListTag::ORD:
				$childType = $this->readByte();
				$children_i = gmp_intval($this->readInt());
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readNBT($childType));
				}
				return new ListTag($name, $childType, $children);
			case CompoundTag::ORD:
				$children = [];
				while(!(($tag = $this->readNBT()) instanceof EndTag))
				{
					array_push($children, $tag);
				}
				return new CompoundTag($name, $children);
			case IntArrayTag::ORD:
				$children_i = gmp_intval($this->readInt());
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readInt());
				}
				return new IntArrayTag($name, $children);
			case LongArrayTag::ORD:
				$children_i = gmp_intval($this->readInt());
				$children = [];
				for($i = 0; $i < $children_i; $i++)
				{
					array_push($children, $this->readLong());
				}
				return new LongArrayTag($name, $children);
			default:
				throw new DomainException("Unsupported NBT Tag: {$type}");
		}
	}

	/**
	 * Read the specified amount of bytes from the read buffer.
	 *
	 * @param int $bytes
	 * @return string
	 * @throws IOException When there are not enough bytes in the buffer to read the given number.
	 */
	function readRaw(int $bytes): string
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < $bytes)
		{
			throw new IOException("There are less than $bytes bytes");
		}
		$str = substr($this->read_buffer, $this->read_buffer_offset, $bytes);
		$this->read_buffer_offset += $bytes;
		return $str;
	}

	/**
	 * Reads a signed long from the read buffer.
	 *
	 * @return GMP
	 * @throws IOException When there are not enough bytes to read a long.
	 */
	function readLong(): GMP
	{
		return $this->readGMP(8, 64, true);
	}

	/**
	 * Reads a float from the read buffer.
	 *
	 * @return float
	 * @throws IOException When there are not enough bytes to read a float.
	 */
	function readFloat(): float
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < 4)
		{
			throw new IOException("There are not enough bytes to read a float");
		}
		$float = unpack("Gfloat", substr($this->read_buffer, $this->read_buffer_offset, 4))["float"];
		$this->read_buffer_offset += 4;
		return $float;
	}

	/**
	 * Reads an unsigned short from the read buffer.
	 *
	 * @return int
	 * @throws IOException When there are not enough bytes to read a short.
	 */
	function readUnsignedShort(): int
	{
		return gmp_intval($this->readGMP(2, 16, false));
	}

	/**
	 * Skips over the given amount of bytes in the read buffer.
	 *
	 * @param int $bytes
	 * @return Connection $this
	 * @throws IOException When there are not enough bytes in the buffer to ignore the given number.
	 */
	function ignoreBytes(int $bytes): Connection
	{
		if(strlen($this->read_buffer) - $this->read_buffer_offset < $bytes)
		{
			throw new IOException("There are less than {$bytes} bytes");
		}
		$this->read_buffer_offset += $bytes;
		return $this;
	}
}

Connection::$zero = gmp_init(0);
for($i = 2; $i <= 64; $i++)
{
	Connection::$pow2[$i] = gmp_pow(2, $i);
}
