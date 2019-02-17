<?php
require_once __DIR__."/../vendor/autoload.php";
final class PacketsTest extends \PHPUnit\Framework\TestCase
{
	function testKeepAlivePackets()
	{
		$con = new \Phpcraft\Connection(339);
		$con->writeLong(1337); // Keep Alive ID
		$con->read_buffer = $con->write_buffer;
		$packet = \Phpcraft\KeepAliveRequestPacket::read($con);
		$this->assertEquals("", $con->read_buffer);
		$this->assertEquals(1337, $packet->keepAliveId);
		$packet = $packet->getResponse();
		$this->assertEquals(1337, $packet->keepAliveId);
		$con = new \Phpcraft\Connection(338);
		$packet->send($con);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals("keep_alive_response", \Phpcraft\Packet::serverboundPacketIdToName($con->readVarInt(), $con->protocol_version));
		$this->assertEquals(1337, $con->readVarInt()); // Keep Alive ID
		$this->assertEquals("", $con->read_buffer);
	}

	function testJoinGamePacket()
	{
		$con = new \Phpcraft\Connection(108);
		$con->writeInt(1337); // Entity ID
		$con->writeByte(3 + 0x8); // Gamemode (Spectator + Hardcore)
		$con->writeInt(-1); // Dimension
		$con->writeByte(2); // Difficulty
		$con->writeByte(0); // Max Players
		$con->writeString(""); // Level Type
		$con->writeBoolean(false); // Reduced Debug Info
		$con->read_buffer = $con->write_buffer;
		$packet = \Phpcraft\JoinGamePacket::read($con);
		$this->assertEquals("", $con->read_buffer);
		$this->assertEquals(1337, $packet->entityId);
		$this->assertEquals(3, $packet->gamemode);
		$this->assertTrue($packet->hardcore);
		$this->assertEquals(-1, $packet->dimension);
		$this->assertEquals(2, $packet->difficulty);
		$con = new \Phpcraft\Connection(107);
		$packet->send($con);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals("join_game", \Phpcraft\Packet::clientboundPacketIdToName($con->readVarInt(), $con->protocol_version));
		$this->assertEquals(1337, $con->readInt()); // Entity ID
		$this->assertEquals(3 + 0x8, $con->readByte()); // Gamemode (Spectator + Hardcore)
		$this->assertEquals(-1, $con->readByte(true)); // Dimension
		$this->assertEquals(2, $con->readByte()); // Difficulty
		$this->assertEquals(100, $con->readByte()); // Max Players
		$this->assertEquals("", $con->readString()); // Level Type
		$this->assertEquals(false, $con->readBoolean()); // Reduced Debug Info
		$this->assertEquals("", $con->read_buffer);
	}
}
