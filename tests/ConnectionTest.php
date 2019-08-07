<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\Connection;
class ConnectionTest
{
	function testReadAndWriteInts()
	{
		$con = new Connection();
		$con->writeInt(1);
		$con->writeInt(-1);
		$con->writeInt("3405691582");
		$con->writeInt("3405691582", true);
		Nose::assert($con->write_buffer === "\x00\x00\x00\x01\xFF\xFF\xFF\xFF\xCA\xFE\xBA\xBE\xCA\xFE\xBA\xBE");
		$con->read_buffer = $con->write_buffer;
		Nose::assert(gmp_cmp($con->readInt(), 1) == 0);
		Nose::assert(gmp_cmp($con->readInt(true), -1) == 0);
		Nose::assert(gmp_cmp($con->readInt(), "3405691582") == 0);
		Nose::assert(gmp_cmp($con->readInt(true), "-889275714") == 0);
		Nose::assert($con->read_buffer === "");
	}

	function testReadAndWriteFloats()
	{
		$con = new Connection();
		$con->writeFloat(1);
		$con->writeFloat(-1);
		$con->writeFloat(0.5);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(1.0, $con->readFloat());
		Nose::assertEquals(-1.0, $con->readFloat());
		Nose::assertEquals(0.5, $con->readFloat());
		Nose::assertEquals("", $con->read_buffer);
	}

	function testReadAndWriteDoubles()
	{
		$con = new Connection();
		$con->writeDouble(1);
		$con->writeDouble(-1);
		$con->writeDouble(0.5);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(1.0, $con->readDouble());
		Nose::assertEquals(-1.0, $con->readDouble());
		Nose::assertEquals(0.5, $con->readDouble());
		Nose::assertEquals("", $con->read_buffer);
	}

	function testWriteVarintAndReadBytes()
	{
		$con = new Connection();
		$con->writeVarInt(255);
		Nose::assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(0b11111111, $con->readByte());
		Nose::assertEquals(0b00000001, $con->readByte());
		Nose::assertEquals("", $con->read_buffer);
	}

	function testWriteBytesAndReadVarint()
	{
		$con = new Connection();
		$con->writeByte(0b11111111);
		$con->writeByte(0b00000001);
		Nose::assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		Nose::assert(gmp_cmp($con->readVarInt(), 255) == 0);
		Nose::assertEquals("", $con->read_buffer);
	}

	function testReadAndWriteString()
	{
		$con = new Connection();
		$con->writeString("Ä");
		Nose::assertEquals(3, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(2, gmp_intval($con->readVarInt()));
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals("Ä", $con->readString());
		Nose::assertEquals("", $con->read_buffer);
	}

	function testReadAndWriteChatObject()
	{
		$chat = [
			"text" => "Hey",
			"color" => "gold"
		];
		$con = new Connection();
		$con->writeChat("Hi");
		$con->writeChat($chat);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(["text" => "Hi"], $con->readChat());
		Nose::assertEquals($chat, $con->readChat());
		Nose::assertEquals("", $con->read_buffer);
	}
}
