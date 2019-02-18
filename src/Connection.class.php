<?php
namespace Phpcraft;
/**
 * A wrapper to read and write from streams.
 * The Connection object can also be utilized without a stream:
 * <pre>$con = new Connection($protocol_version);
 * $packet = new ChatMessagePacket(["text" => "Hello, world!"]);
 * $packet->send($con);
 * echo hex2bin($con->write_buffer)."\n";</pre>
 */
class Connection
{
	/**
	 * The protocol version that is used for this connection.
	 * @var integer $protocol_version
	 */
	public $protocol_version;
	/**
	 * The stream of the connection of null.
	 * @var resource $stream
	 */
	public $stream;
	/**
	 * The amount of bytes a packet needs for it to be compressed as an integer or -1 if disabled.
	 * @var integer $compression_threshold
	 */
	public $compression_threshold = -1;
	/**
	 * The state of the connection.
	 * 1 stands for status, 2 for logging in, and 3 for playing.
	 * @var integer $state
	 */
	public $state;
	/**
	 * The write buffer binary string.
	 * @var string $write_buffer
	 */
	public $write_buffer = "";
	/**
	 * The read buffer binary string.
	 * @var string $read_buffer
	 */
	public $read_buffer = "";

	/**
	 * The constructor.
	 * @param integer $protocol_version
	 * @param resource $stream
	 */
	function __construct($protocol_version = -1, $stream = null)
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
	 * @return boolean
	 */
	function isOpen()
	{
		return $this->stream != null && @feof($this->stream) === false;
	}

	/**
	 * Closes the stream.
	 * @return void
	 */
	function close()
	{
		if($this->stream != null)
		{
			@fclose($this->stream);
			$this->stream = null;
		}
	}

	/**
	 * Adds a byte to the write buffer.
	 * @param integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	function writeByte($value, $signed = false)
	{
		$this->write_buffer .= pack(($signed ? "c" : "C"), $value);
		return $this;
	}

	/**
	 * Adds a boolean to the write buffer.
	 * @param boolean $value
	 * @return Connection this
	 */
	function writeBoolean($value)
	{
		$this->write_buffer .= pack("C", ($value ? 1 : 0));
		return $this;
	}

	/**
	 * Adds a VarInt to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeVarInt($value)
	{
		$this->write_buffer .= Phpcraft::intToVarInt($value);
		return $this;
	}

	/**
	 * Adds a string to the write buffer.
	 * @param string $value
	 * @return Connection $this
	 */
	function writeString($value)
	{
		$this->write_buffer .= Phpcraft::intToVarInt(strlen($value)).$value;
		return $this;
	}

	/**
	 * Adds the byte string to the write buffer.
	 * @param string $value
	 * @return Connection $this
	 */
	function writeRaw($value)
	{
		$this->write_buffer .= $value;
		return $this;
	}

	/**
	 * Adds a short to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeShort($value)
	{
		$this->write_buffer .= pack("n", $value);
		return $this;
	}

	/**
	 * Adds a float to the write buffer.
	 * @param float $value
	 * @return Connection $this
	 */
	function writeFloat($value)
	{
		$this->write_buffer .= pack("G", $value);
		return $this;
	}

	/**
	 * Adds an integer to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeInt($value)
	{
		$this->write_buffer .= pack("N", $value);
		return $this;
	}

	/**
	 * Adds a long to the write buffer.
	 * @param integer $value
	 * @return Connection $this
	 */
	function writeLong($value)
	{
		$this->write_buffer .= pack("J", $value);
		return $this;
	}

	/**
	 * Adds a double to the write buffer.
	 * @param float $value
	 * @return Connection $this
	 */
	function writeDouble($value)
	{
		$this->write_buffer .= pack("E", $value);
		return $this;
	}

	/**
	 * Adds a position to the write buffer.
	 * @param integer $x
	 * @param integer $y
	 * @param integer $z
	 * @return Connection $this
	 */
	function writePosition($x, $y, $z)
	{
		$this->writeLong((($x & 0x3FFFFFF) << 38) | (($y & 0xFFF) << 26) | ($z & 0x3FFFFFF));
		return $this;
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 * @param string $name The name of the new packet. For a list of packet names, check the source code of Packet.
	 * @return Connection $this
	 */
	function startPacket($name)
	{
		$this->write_buffer = Phpcraft::intToVarInt(Packet::getId($name, $this->protocol_version));
		return $this;
	}

	/**
	 * Sends the contents of the write buffer over the stream and clears the write buffer or does nothing if there is no stream.
	 * @param boolean $raw When true, the write buffer is sent as-is, without length prefix or compression, which you probably don't want.
	 * @throws Exception If the connection is not open.
	 * @return Connection $this
	 */
	function send($raw = false)
	{
		if($this->stream != null)
		{
			if(@feof($this->stream) !== false)
			{
				throw new \Phpcraft\Exception("Can't send to connection that's not open.");
			}
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
						$length_varint = Phpcraft::intToVarInt($length);
						fwrite($this->stream, Phpcraft::intToVarInt($compressed_length + strlen($length_varint)).$length_varint.$compressed);
					}
					else
					{
						fwrite($this->stream, Phpcraft::intToVarInt($length + 1)."\x00".$this->write_buffer);
					}
				}
				else
				{
					fwrite($this->stream, Phpcraft::intToVarInt($length).$this->write_buffer);
				}
			}
			$this->write_buffer = "";
		}
		return $this;
	}

	/**
	 * Puts a raw bytes into the read buffer, which you probably don't want.
	 * @see Connection::readPacket
	 * @param float $timeout The amount of seconds to wait before read is aborted.
	 * @param integer $bytes The exact amount of bytes you would like to receive.
	 * @return boolean True on success.
	 */
	function readRawPacket($timeout = 3.000, $bytes = 0)
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
	 * @see Connection::readRawPacket
	 * @param float $timeout The amount of seconds to wait before read is aborted.
	 * @throws Exception When the packet length or packet id VarInt is too big.
	 * @return integer|boolean The packet's id or false if no packet was received within the time limit.
	 * @see Packet::clientboundPacketIdToName()
	 * @see Packet::serverboundPacketIdToName()
	 */
	function readPacket($timeout = 3.000)
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
				throw new \Phpcraft\Exception("VarInt is too big");
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
			$uncompressed_length = $this->readVarInt();
			if($uncompressed_length > 0)
			{
				$this->read_buffer = @gzuncompress($this->read_buffer, $uncompressed_length);
				if(!$this->read_buffer)
				{
					return false;
				}
			}
		}
		return $this->readVarInt();
	}

	/**
	 * Read the specified amount of bytes from the read buffer.
	 * @param integer $bytes
	 * @return string
	 */
	function readRaw($bytes)
	{
		$str = substr($this->read_buffer, 0, $bytes);
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $str;
	}

	/**
	 * Reads an integer encoded as a VarInt from the read buffer.
	 * @throws Exception When there are not enough bytes to read a VarInt or the VarInt is too big.
	 * @return integer
	 */
	function readVarInt()
	{
		$value = 0;
		$read = 0;
		do
		{
			if(strlen($this->read_buffer) == 0)
			{
				throw new \Phpcraft\Exception("Not enough bytes to read VarInt");
			}
			$byte = $this->readByte();
			$value |= (($byte & 0b01111111) << (7 * $read++));
			if($read > 5)
			{
				throw new \Phpcraft\Exception("VarInt is too big");
			}
		}
		while(($byte & 0b10000000) != 0);
		if($value >= 0x80000000)
		{
			$value = ((($value ^ 0xFFFFFFFF) + 1) * -1);
		}
		return $value;
	}

	/**
	 * Reads a string from the read buffer.
	 * @param integer $maxLength
	 * @throws Exception When there are not enough bytes to read a string or the string exceeds `$maxLength`.
	 * @return string
	 */
	function readString($maxLength = 32767)
	{
		$length = $this->readVarInt();
		if($length == 0)
		{
			return "";
		}
		if($length > (($maxLength * 4) + 3))
		{
			throw new \Phpcraft\Exception("String length {$length} exceeds maximum of ".(($maxLength * 4) + 3));
		}
		if($length > strlen($this->read_buffer))
		{
			throw new \Phpcraft\Exception("Not enough bytes to read string with length {$length}");
		}
		$str = substr($this->read_buffer, 0, $length);
		$this->read_buffer = substr($this->read_buffer, $length);
		return $str;
	}

	/**
	 * Reads a byte from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a byte.
	 * @return integer
	 */
	function readByte($signed = false)
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read byte");
		}
		$byte = unpack(($signed ? "c" : "C")."byte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		return $byte;
	}

	/**
	 * Reads a boolean from the read buffer.
	 * @throws Exception When there are not enough bytes to read a boolean.
	 * @return boolean
	 */
	function readBoolean()
	{
		if(strlen($this->read_buffer) < 1)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read boolean");
		}
		$byte = unpack("cbyte", substr($this->read_buffer, 0, 1))["byte"];
		$this->read_buffer = substr($this->read_buffer, 1);
		return $byte != 0;
	}

	/**
	 * Reads a short from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a short.
	 * @return integer
	 */
	function readShort($signed = true)
	{
		if(strlen($this->read_buffer) < 2)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read short");
		}
		$short = unpack("nshort", substr($this->read_buffer, 0, 2))["short"];
		$this->read_buffer = substr($this->read_buffer, 2);
		if($signed && $short >= 0x8000)
		{
			return ((($short ^ 0xFFFF) + 1) * -1);
		}
		return $short;
	}

	/**
	 * Reads an integer from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read an integer.
	 * @return integer
	 */
	function readInt($signed = true)
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read int");
		}
		$int = unpack("Nint", substr($this->read_buffer, 0, 4))["int"];
		$this->read_buffer = substr($this->read_buffer, 4);
		if($signed && $int >= 0x80000000)
		{
			return ((($int ^ 0xFFFFFFFF) + 1) * -1);
		}
		return $int;
	}

	/**
	 * Reads a long from the read buffer.
	 * @param boolean $signed
	 * @throws Exception When there are not enough bytes to read a long.
	 * @return integer
	 */
	function readLong($signed = true)
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read long");
		}
		$long = unpack("Jlong", substr($this->read_buffer, 0, 8))["long"];
		$this->read_buffer = substr($this->read_buffer, 8);
		if($signed && $long >= 0x8000000000000000)
		{
			return ((($long ^ 0xFFFFFFFFFFFFFFFF) + 1) * -1);
		}
		return $long;
	}

	/**
	 * Reads a position from the read buffer.
	 * @return array An array containing x, y, and z of the position.
	 */
	function readPosition()
	{
		$val = readLong(false);
		$x = $val >> 38;
		$y = ($val >> 26) & 0xFFF;
		$z = $val << 38 >> 38;
		return [$x, $y, $z];
	}

	/**
	 * Reads a float from the read buffer.
	 * @return float
	 */
	function readFloat()
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read float");
		}
		$float = unpack("Gfloat", substr($this->read_buffer, 0, 4))["float"];
		$this->read_buffer = substr($this->read_buffer, 4);
		return $float;
	}

	/**
	 * Reads a double from the read buffer.
	 * @return float
	 */
	function readDouble()
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read double");
		}
		$double = unpack("Edouble", substr($this->read_buffer, 0, 8))["double"];
		$this->read_buffer = substr($this->read_buffer, 8);
		return $double;
	}

	/**
	 * Reads a binary string consisting of 16 bytes.
	 * @return string
	 */
	function readUUIDBytes()
	{
		if(strlen($this->read_buffer) < 16)
		{
			throw new \Phpcraft\Exception("Not enough bytes to read UUID");
		}
		$uuid = substr($this->read_buffer, 0, 16);
		$this->read_buffer = substr($this->read_buffer, 16);
		return $uuid;
	}

	/**
	 * Reads an NbtTag.
	 * @param boolean $inList Ignore this parameter.
	 * @return NbtTag
	 */
	function readNBT($type = 0)
	{
		$inList = $type > 0;
		if(!$inList)
		{
			$type = $this->readByte();
		}
		$name = ($type == 0 || $inList) ? "" : $this->readRaw($this->readShort());
		switch($type)
		{
			case 0:
			return new \Phpcraft\NbtEnd();

			case 1:
			return new \Phpcraft\NbtByte($name, $this->readByte());

			case 2:
			return new \Phpcraft\NbtShort($name, $this->readShort());

			case 3:
			return new \Phpcraft\NbtInt($name, $this->readInt());

			case 4:
			return new \Phpcraft\NbtLong($name, $this->readLong());

			case 5:
			return new \Phpcraft\NbtFloat($name, $this->readFloat());

			case 6:
			return new \Phpcraft\NbtDouble($name, $this->readDouble());

			case 7:
			$children_i = $this->readInt();
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readByte());
			}
			return new \Phpcraft\NbtByteArray($name, $children);

			case 8:
			return new \Phpcraft\NbtString($name, $this->readRaw($this->readShort()));

			case 9:
			$childType = $this->readByte();
			$children_i = $this->readInt();
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readNBT($childType));
			}
			return new \Phpcraft\NbtList($name, $childType, $children);

			case 10:
			$children = [];
			while(!(($tag = $this->readNBT()) instanceof \Phpcraft\NbtEnd))
			{
				array_push($children, $tag);
			}
			return new \Phpcraft\NbtCompound($name, $children);

			case 11:
			$children_i = $this->readInt();
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readInt());
			}
			return new \Phpcraft\NbtIntArray($name, $children);

			case 12:
			$children_i = $this->readInt();
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readLong());
			}
			return new \Phpcraft\NbtLongArray($name, $children);

			default:
			throw new \Phpcraft\Exception("Unsupported NBT Tag: {$type}");
		}
	}

	/**
	 * Skips over the given amount of bytes in the read buffer.
	 * @param integer $bytes
	 * @return Connection $this
	 */
	function ignoreBytes($bytes)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new \Phpcraft\Exception("There are less than {$bytes} bytes");
		}
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $this;
	}
}
