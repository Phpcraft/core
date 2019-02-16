<?php
require_once __DIR__."/../vendor/autoload.php";
final class PacketsTest extends \PHPUnit\Framework\TestCase
{
	function testKeepAlivePackets()
	{
		$con = new \Phpcraft\Connection(339);
		$con->writeLong(1337);
		$con->read_buffer = $con->write_buffer;
		$packet = \Phpcraft\KeepAliveRequestPacket::read($con);
		$this->assertEquals(1337, $packet->keepAliveId);
		$packet = $packet->getResponse();
		$this->assertEquals(1337, $packet->keepAliveId);
		$con = new \Phpcraft\Connection(338);
		$packet->send($con);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals("keep_alive_response", \Phpcraft\Packet::serverboundPacketIdToName($con->readVarInt(), 338));
		$this->assertEquals(1337, $con->readVarInt());
	}
}
