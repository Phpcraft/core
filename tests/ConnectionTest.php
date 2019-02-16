<?php
require __DIR__."/../vendor/autoload.php";
final class ConnectionTest extends \PHPUnit\Framework\TestCase
{
	public function testReadAndWriteInts()
	{
		$con = new \Phpcraft\Connection();
		$con->writeInt(1);
		$con->writeInt(-1);
		$this->assertEquals("\x00\x00\x00\x01\xFF\xFF\xFF\xFF", $con->write_buffer);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(1, $con->readInt());
		$this->assertEquals(-1, $con->readInt());
	}

	public function testReadAndWriteFloats()
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

	public function testReadAndWriteDoubles()
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

	public function testWriteVarintAndReadBytes()
	{
		$con = new \Phpcraft\Connection();
		$con->writeVarInt(255);
		$this->assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(0b11111111, $con->readByte());
		$this->assertEquals(0b00000001, $con->readByte());
	}

	public function testWriteBytesAndReadVarint()
	{
		$con = new \Phpcraft\Connection();
		$con->writeByte(0b11111111);
		$con->writeByte(0b00000001);
		$this->assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(255, $con->readVarInt());
	}

	public function testReadAndWriteStrings()
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
