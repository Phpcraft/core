<?php
namespace Phpcraft;
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
			fclose($this->stream);
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
	 * Adds a chat object to the read buffer.
	 * @param array|string $value The chat object or a strings that will be converted into a chat object.
	 * @return Connection $this
	 * @throws Exception
	 */
	function writeChat($value)
	{
		if(gettype($value) == "string")
		{
			$value = Phpcraft::textToChat($value);
		}
		else if(gettype($value) != "array")
		{
			throw new Exception("Invalid argument type: ".gettype($value));
		}
		$this->writeString(json_encode($value));
		return null;
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
	 * @param boolean $signed
	 * @return Connection $this
	 */
	function writeShort($value, $signed = false)
	{
		if($signed && $value > 0x7FFF)
		{
			$value -= 0x10000;
		}
		$this->write_buffer .= pack("n", $value);
		return $this;
	}

	/**
	 * Adds an integer to the write buffer.
	 * @param integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	function writeInt($value, $signed = false)
	{
		if($signed && $value > 0x7FFFFFFF)
		{
			$value -= 0x100000000;
		}
		$this->write_buffer .= pack("N", $value);
		return $this;
	}

	/**
	 * Adds a long to the write buffer.
	 * @param integer $value
	 * @param boolean $signed
	 * @return Connection $this
	 */
	function writeLong($value, $signed = false)
	{
		if($signed && $value > 0x7FFFFFFFFFFFFFFF)
		{
			$value -= 0x10000000000000000;
		}
		$this->write_buffer .= pack("J", $value);
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
	 * Adds a position encoded as one long to the write buffer.
	 * @param Position $pos
	 * @return Connection $this
	 */
	function writePosition(Position $pos)
	{
		return $this->writeLong((($pos->x & 0x3FFFFFF) << 38) | (($pos->y & 0xFFF) << 26) | ($pos->z & 0x3FFFFFF));
	}

	/**
	 * Adds a position encoded as three double to the write buffer.
	 * @param Position $pos
	 * @return Connection $this
	 */
	function writePrecisePosition(Position $pos)
	{
		$this->writeDouble($pos->x);
		$this->writeDouble($pos->y);
		return $this->writeDouble($pos->z);
	}

	/**
	 * Adds a position encoded as three ints to the write buffer.
	 * @param Position $pos
	 * @return Connection $this
	 */
	function writeFixedPointPosition(Position $pos)
	{
		$this->writeInt(intval($pos->x * 32));
		$this->writeInt(intval($pos->y * 32));
		return $this->writeInt(intval($pos->z * 32));
	}

	/**
	 * Adds a slot to the write buffer.
	 * @param Slot $slot
	 * @return Connection $this
	 * @throws Exception
	 */
	function writeSlot(Slot $slot)
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
						if(!($slot->nbt instanceof NbtCompound))
						{
							throw new Exception("filled_map is missing ID.");
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
	 * @param UUID $uuid
	 * @return Connection $this
	 */
	function writeUUID(UUID $uuid)
	{
		$this->write_buffer .= $uuid->binary;
		return $this;
	}

	/**
	 * Clears the write buffer and starts a new packet.
	 * @param string|integer $packet The name or ID of the new packet.
	 * @return Connection $this
	 * @throws Exception
	 */
	function startPacket($packet)
	{
		if(gettype($packet) == "string")
		{
			$packetId = PacketId::get($packet);
			if(!$packetId)
			{
				throw new Exception("Unknown packet name: ".$packet);
			}
			$packet = $packetId->getId($this->protocol_version);
		}
		else if(gettype($packet) != "integer")
		{
			throw new Exception("Packet has to be either string or integer.");
		}
		$this->write_buffer = Phpcraft::intToVarInt($packet);
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
				throw new Exception("Can't send to connection that's not open.");
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
						$length_varint = Phpcraft::intToVarInt($length);
						fwrite($this->stream, Phpcraft::intToVarInt($compressed_length + strlen($length_varint)).$length_varint.$compressed) or $this->close();
					}
					else
					{
						fwrite($this->stream, Phpcraft::intToVarInt($length + 1)."\x00".$this->write_buffer) or $this->close();
					}
				}
				else
				{
					fwrite($this->stream, Phpcraft::intToVarInt($length).$this->write_buffer) or $this->close();
				}
			}
			stream_set_blocking($this->stream, false);
			$this->write_buffer = "";
		}
		return $this;
	}

	/**
	 * Puts raw bytes from the stream into the read buffer.
	 * @see Connection::readPacket
	 * @param float $timeout The amount of seconds to wait before the read is aborted.
	 * @param integer $bytes The exact amount of bytes you would like to receive. 0 means read up to 8 KiB.
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
				throw new Exception("VarInt is too big");
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
				throw new Exception("There are not enough bytes to read a VarInt.");
			}
			$byte = $this->readByte();
			$value |= (($byte & 0b01111111) << (7 * $read++));
			if($read > 5)
			{
				throw new Exception("VarInt is too big");
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
	 * @param integer $maxLength The maxmium amount of bytes this string may use.
	 * @param integer $minLength The minimum amount of bytes this string must use.
	 * @throws Exception When there are not enough bytes to read a string or the string exceeds $maxLength.
	 * @return string
	 */
	function readString($maxLength = 32767, $minLength = -1)
	{
		$length = $this->readVarInt();
		if($length == 0)
		{
			return "";
		}
		if($length > $maxLength)
		{
			throw new Exception("The string on the wire apparently has {$length} bytes which exceeds {$maxLength}.");
		}
		if($length < $minLength)
		{
			throw new Exception("This string on the wire apparently has {$length} bytes but at least {$minLength} are required.");
		}
		if($length > strlen($this->read_buffer))
		{
			throw new Exception("String on the wire is apparently {$length} bytes long, but that exceeds the bytes in the read buffer.");
		}
		$str = substr($this->read_buffer, 0, $length);
		$this->read_buffer = substr($this->read_buffer, $length);
		return $str;
	}

	/**
	 * Reads a chat object from the read buffer.
	 * @throws Exception When there are not enough bytes to read the string.
	 * @return array
	 */
	function readChat()
	{
		return json_decode($this->readString(), true);
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
			throw new Exception("There are not enough bytes to read a byte.");
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
			throw new Exception("There are not enough bytes to read a boolean.");
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
			throw new Exception("There are not enough bytes to read a short.");
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
	function readInt($signed = false)
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new Exception("There are not enough bytes to read a int.");
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
	function readLong($signed = false)
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new Exception("There are not enough bytes to read a long.");
		}
		$long = gmp_import(substr($this->read_buffer, 0, 8));
		$this->read_buffer = substr($this->read_buffer, 8);
		if($signed && gmp_cmp($long, gmp_pow(2, 63)) >= 0)
		{
			$long = gmp_sub($long, gmp_pow(2, 64));
		}
		return gmp_intval($long);
	}

	/**
	 * Reads a position encoded as one long from the read buffer.
	 * @throws Exception When there are not enough bytes to read a position.
	 * @return Position
	 */
	function readPosition()
	{
		$val = $this->readLong();
		$x = $val >> 38;
		$y = ($val >> 26) & 0xFFF;
		$z = $val << 38 >> 38;
		return new Position($x, $y, $z);
	}

	/**
	 * Reads a position encoded as three doubles from the read buffer.
	 * @throws Exception When there are not enough bytes to read a position.
	 * @return Position
	 */
	function readPrecisePosition()
	{
		return new Position($this->readDouble(), $this->readDouble(), $this->readDouble());
	}

	/**
	 * Reads a position encoded as three ints from the read buffer.
	 * @throws Exception When there are not enough bytes to read a position.
	 * @return Position
	 */
	function readFixedPointPosition()
	{
		return new Position($this->readInt(true) / 32, $this->readInt(true) / 32, $this->readInt(true) / 32);
	}

	/**
	 * Reads a float from the read buffer.
	 * @throws Exception When there are not enough bytes to read a float.
	 * @return float
	 */
	function readFloat()
	{
		if(strlen($this->read_buffer) < 4)
		{
			throw new Exception("There are not enough bytes to read a float.");
		}
		$float = unpack("Gfloat", substr($this->read_buffer, 0, 4))["float"];
		$this->read_buffer = substr($this->read_buffer, 4);
		return $float;
	}

	/**
	 * Reads a double from the read buffer.
	 * @throws Exception When there are not enough bytes to read a double.
	 * @return float
	 */
	function readDouble()
	{
		if(strlen($this->read_buffer) < 8)
		{
			throw new Exception("There are not enough bytes to read a double.");
		}
		$double = unpack("Edouble", substr($this->read_buffer, 0, 8))["double"];
		$this->read_buffer = substr($this->read_buffer, 8);
		return $double;
	}

	/**
	 * Reads a UUID.
	 * @throws Exception When there are not enough bytes to read a UUID.
	 * @return UUID
	 */
	function readUUID()
	{
		if(strlen($this->read_buffer) < 16)
		{
			throw new Exception("There are not enough bytes to read a UUID.");
		}
		$uuid = new UUID(substr($this->read_buffer, 0, 16));
		$this->read_buffer = substr($this->read_buffer, 16);
		return $uuid;
	}

	/**
	 * Reads an NbtTag.
	 * @param int $type Ignore this parameter.
	 * @return NbtTag
	 * @throws Exception
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
			return new NbtEnd();

			case 1:
			return new NbtByte($name, $this->readByte(true));

			case 2:
			return new NbtShort($name, $this->readShort(true));

			case 3:
			return new NbtInt($name, $this->readInt(true));

			case 4:
			return new NbtLong($name, $this->readLong(true));

			case 5:
			return new NbtFloat($name, $this->readFloat());

			case 6:
			return new NbtDouble($name, $this->readDouble());

			case 7:
			$children_i = $this->readInt(true);
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readByte());
			}
			return new NbtByteArray($name, $children);

			case 8:
			return new NbtString($name, $this->readRaw($this->readShort()));

			case 9:
			$childType = $this->readByte();
			$children_i = $this->readInt(true);
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readNBT($childType));
			}
			return new NbtList($name, $childType, $children);

			case 10:
			$children = [];
			while(!(($tag = $this->readNBT()) instanceof NbtEnd))
			{
				array_push($children, $tag);
			}
			return new NbtCompound($name, $children);

			case 11:
			$children_i = $this->readInt(true);
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readInt(true));
			}
			return new NbtIntArray($name, $children);

			case 12:
			$children_i = $this->readInt(true);
			$children = [];
			for($i = 0; $i < $children_i; $i++)
			{
				array_push($children, $this->readLong());
			}
			return new NbtLongArray($name, $children);

			default:
			throw new Exception("Unsupported NBT Tag: {$type}");
		}
	}

	/**
	 * Reads a Slot.
	 * @param boolean $additional_processing Whether additional processing should occur to properly receive pre-1.13 data. You should only set this to false if you want a lazy read, and don't even care about the slot.
	 * @return Slot
	 * @throws Exception
	 */
	function readSlot($additional_processing = true)
	{
		$slot = new Slot();
		if($this->protocol_version >= 402)
		{
			if(!$this->readBoolean())
			{
				return $slot;
			}
			$slot->item = Item::getById($this->readVarInt(), $this->protocol_version);
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
	 * @param integer $bytes
	 * @return Connection $this
	 * @throws Exception
	 */
	function ignoreBytes($bytes)
	{
		if(strlen($this->read_buffer) < $bytes)
		{
			throw new Exception("There are less than {$bytes} bytes");
		}
		$this->read_buffer = substr($this->read_buffer, $bytes);
		return $this;
	}
}
