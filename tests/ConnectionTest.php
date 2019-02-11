<?php
require __DIR__."/../vendor/autoload.php";
final class ConnectionTest extends \PHPUnit\Framework\TestCase
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
}
