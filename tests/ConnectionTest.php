<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\
{ChatComponent, Connection, Point3D, ServerConnection};
class ConnectionTest
{
	function testResolveAddress()
	{
		Nose::assertEquals(ServerConnection::resolveAddress("local.phpcraft.de"), [
			"hostname" => "localhost",
			"port" => 25565
		]);
		Nose::assertEquals(ServerConnection::resolveAddress("local.phpcraft.de:1337"), [
			"hostname" => "local.phpcraft.de",
			"port" => 1337
		]);
		Nose::assertEquals(ServerConnection::resolveAddress("1.1.1.1"), [
			"hostname" => "1.1.1.1",
			"port" => 25565
		]);
		Nose::assertEquals(ServerConnection::resolveAddress("1.1.1.1:1337"), [
			"hostname" => "1.1.1.1",
			"port" => 1337
		]);
		Nose::assertEquals(ServerConnection::resolveAddress("leet.apimon.de"), [
			"hostname" => "leet.apimon.de",
			"port" => 25565
		]);
		Nose::assertEquals(ServerConnection::resolveAddress("leet.apimon.de:1337"), [
			"hostname" => "leet.apimon.de",
			"port" => 1337
		]);
	}

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

	function testReadAndWriteChatComponent()
	{
		$chat = ChatComponent::fromArray([
			"text" => "Hey",
			"color" => "gold"
		]);
		$con = new Connection();
		$con->writeChat(ChatComponent::text("Hi"));
		$con->writeChat($chat);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals(["text" => "Hi"], $con->readChat()
												 ->toArray());
		Nose::assertEquals($chat->toArray(), $con->readChat()
												 ->toArray());
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
