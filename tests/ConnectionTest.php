<?php
require_once __DIR__."/../vendor/autoload.php";
final class ConnectionTest extends \PHPUnit\Framework\TestCase
{
	function testReadAndWriteInts()
	{
		$con = new \Phpcraft\Connection();
		$con->writeInt(1);
		$con->writeInt(-1, true);
		$this->assertEquals("\x00\x00\x00\x01\xFF\xFF\xFF\xFF", $con->write_buffer);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(1, $con->readInt());
		$this->assertEquals(-1, $con->readInt(true));
	}

	function testReadAndWriteFloats()
	{
		$con = new \Phpcraft\Connection();
		$con->writeFloat(1);
		$con->writeFloat(-1);
		$con->writeFloat(0.5);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(1, $con->readFloat());
		$this->assertEquals(-1, $con->readFloat());
		$this->assertEquals(0.5, $con->readFloat());
	}

	function testReadAndWriteDoubles()
	{
		$con = new \Phpcraft\Connection();
		$con->writeDouble(1);
		$con->writeDouble(-1);
		$con->writeDouble(0.5);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(1, $con->readDouble());
		$this->assertEquals(-1, $con->readDouble());
		$this->assertEquals(0.5, $con->readDouble());
	}

	function testWriteVarintAndReadBytes()
	{
		$con = new \Phpcraft\Connection();
		$con->writeVarInt(255);
		$this->assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(0b11111111, $con->readByte());
		$this->assertEquals(0b00000001, $con->readByte());
	}

	function testWriteBytesAndReadVarint()
	{
		$con = new \Phpcraft\Connection();
		$con->writeByte(0b11111111);
		$con->writeByte(0b00000001);
		$this->assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(255, $con->readVarInt());
	}

	function testReadAndWriteString()
	{
		$con = new \Phpcraft\Connection();
		$con->writeString("Ä");
		$this->assertEquals(3, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(2, $con->readVarInt());
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals("Ä", $con->readString());
	}
}
