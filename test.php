<?php
require "vendor/autoload.php";
final class PhpcraftTest extends \PHPUnit\Framework\TestCase
{
	public function testInt()
	{
		$con = new \Phpcraft\Connection();
		$con->writeInt(1);
		$con->writeInt(-1);
		$this->assertEquals("\x00\x00\x00\x01\xFF\xFF\xFF\xFF", $con->write_buffer);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals(1, $con->readInt());
		$this->assertEquals(-1, $con->readInt());
	}

	public function testUUID()
	{
		$this->assertEquals("e0603b59-2edc-45f7-acc7-b0cccd6656e1", \Phpcraft\Phpcraft::addHypensToUUID("e0603b592edc45f7acc7b0cccd6656e1"));
		$this->assertEquals("e0603b59-2edc-45f7-acc7-b0cccd6656e1", \Phpcraft\Phpcraft::addHypensToUUID("e0603b59-2edc-45f7-acc7-b0cccd6656e1"));
	}
}
