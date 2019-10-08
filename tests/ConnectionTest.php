<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\
{Connection, Point3D};
class ConnectionTest
{
	function testReadAndWriteInts()
	{
		$con = new Connection();
		$con->writeInt(1);
		$con->writeInt("3405691582");
		Nose::assert($con->write_buffer === "\x00\x00\x00\x01\xCA\xFE\xBA\xBE");
		$con->read_buffer = $con->write_buffer;
		Nose::assert(gmp_cmp($con->readInt(), 1) == 0);
		Nose::assert(gmp_cmp($con->readInt(), "-889275714") == 0);
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testReadAndWriteFloats()
	{
		$con = new Connection();
		$con->writeFloat(1);
		$con->writeFloat(-1);
		$con->writeFloat(0.5);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals($con->readFloat(), 1.0);
		Nose::assertEquals($con->readFloat(), -1.0);
		Nose::assertEquals($con->readFloat(), 0.5);
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testReadAndWriteDoubles()
	{
		$con = new Connection();
		$con->writeDouble(1);
		$con->writeDouble(-1);
		$con->writeDouble(0.5);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals($con->readDouble(), 1.0);
		Nose::assertEquals($con->readDouble(), -1.0);
		Nose::assertEquals($con->readDouble(), 0.5);
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testWriteVarIntAndReadUnsignedBytes()
	{
		$con = new Connection();
		$con->writeVarInt(255);
		Nose::assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(0b11111111, $con->readUnsignedByte());
		Nose::assertEquals(0b00000001, $con->readUnsignedByte());
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testWriteBytesAndReadVarInt()
	{
		$con = new Connection();
		$con->writeByte(0b11111111);
		$con->writeByte(0b00000001);
		Nose::assertEquals(2, strlen($con->write_buffer));
		$con->read_buffer = $con->write_buffer;
		Nose::assert(gmp_cmp($con->readVarInt(), 255) == 0);
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testReadAndWriteString()
	{
		$con = new Connection();
		$con->writeString("Ä");
		Nose::assertEquals(3, strlen($con->write_buffer));
		$con->setReadBuffer($con->write_buffer);
		Nose::assertEquals(2, gmp_intval($con->readVarInt()));
		$con->setReadBuffer($con->write_buffer);
		Nose::assertEquals("Ä", $con->readString());
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
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
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testReadAndWritePosition()
	{
		foreach([
			47,
			472
		] as $pv)
		{
			$con = new Connection($pv);
			for($x = -3; $x <= 3; $x++)
			{
				for($y = -3; $y <= 3; $y++)
				{
					for($z = -3; $z <= 3; $z++)
					{
						$pos = new Point3D($x, $y, $z);
						$con->writePosition($pos);
						$con->setReadBuffer($con->write_buffer);
						$con->write_buffer = "";
						$pos_ = $con->readPosition();
						Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
						Nose::assertTrue($pos->equals($pos_));
					}
				}
			}
		}
	}
}
