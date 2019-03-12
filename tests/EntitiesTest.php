<?php
require_once __DIR__."/../vendor/autoload.php";
final class EntitiesTest extends \PHPUnit\Framework\TestCase
{
	function testEntityBase()
	{
		$metadata = new \Phpcraft\EntityBase();
		$metadata->burning = true;
		$metadata->elytraing = true;
		$metadata->custom_name = ["text" => "Test", "color" => "yellow"];
		$con = new \Phpcraft\Connection(47);
		$metadata->write($con);
		$this->assertTrue(strpos($con->write_buffer, "§eTest") !== false);
		$con->read_buffer = $con->write_buffer;
		$read_metadata = (new \Phpcraft\EntityBase())->read($con);
		$this->assertTrue($read_metadata->burning);
		$this->assertFalse($read_metadata->crouching);
		$this->assertFalse($read_metadata->elytraing);
		$this->assertEquals($metadata->custom_name, $read_metadata->custom_name);
	}
}
