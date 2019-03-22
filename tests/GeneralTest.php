<?php
require_once __DIR__."/../vendor/autoload.php";
final class GeneralTest extends \PHPUnit\Framework\TestCase
{
	function testTextToChat()
	{
		$this->assertEquals(["text" => "&1Test", "color" => "black"], \Phpcraft\Phpcraft::textToChat("§0&1Test", false));
		$this->assertEquals(["text" => "&r&0Test"], \Phpcraft\Phpcraft::textToChat("&r&0Test", false));
		$this->assertEquals(["text" => "Test", "color" => "black"], \Phpcraft\Phpcraft::textToChat("&r&0Test", true));
	}

	function testChatToText()
	{
		$this->assertEquals("Test", \Phpcraft\Phpcraft::chatToText(["text" => "Test", "color" => "black"], 0));
		$this->assertEquals("§r§0Test", \Phpcraft\Phpcraft::chatToText(["text" => "Test", "color" => "black"], 2));
		$this->assertEquals("&r&0Test", \Phpcraft\Phpcraft::chatToText(["text" => "Test", "color" => "black"], 3));
		$this->assertEquals('<span style="color:#000">Test</span>', \Phpcraft\Phpcraft::chatToText(["text" => "Test", "color" => "black"], 4));
	}

	function testUuid()
	{
		$uuid = \Phpcraft\UUID::fromString("e0603b592edc45f7acc7b0cccd6656e1");
		$this->assertEquals($uuid, \Phpcraft\UUID::fromString("e0603b59-2edc-45f7-acc7-b0cccd6656e1"));
		$this->assertEquals("e0603b592edc45f7acc7b0cccd6656e1", $uuid->toString());
		$this->assertEquals("e0603b59-2edc-45f7-acc7-b0cccd6656e1", $uuid->toString(true));
		$this->assertEquals("a36e854defad58cdbd0084259b83901d", $uuid->v5("Hello, world!")->toString());
		$this->assertEquals(3764410081, $uuid->toInt());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff0-ffff-fff0-ffff-fff0fffffff0")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff0-ffff-fff0-ffff-fff1fffffff1")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff0-ffff-fff1-ffff-fff0fffffff1")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff0-ffff-fff1-ffff-fff1fffffff0")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff1-ffff-fff0-ffff-fff0fffffff1")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff1-ffff-fff0-ffff-fff1fffffff0")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff1-ffff-fff1-ffff-fff0fffffff0")->isSlim());
		$this->assertFalse(\Phpcraft\UUID::fromString("fffffff1-ffff-fff1-ffff-fff1fffffff1")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff0-ffff-fff0-ffff-fff0fffffff1")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff0-ffff-fff0-ffff-fff1fffffff0")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff0-ffff-fff1-ffff-fff0fffffff0")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff0-ffff-fff1-ffff-fff1fffffff1")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff1-ffff-fff0-ffff-fff0fffffff0")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff1-ffff-fff0-ffff-fff1fffffff1")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff1-ffff-fff1-ffff-fff0fffffff1")->isSlim());
		$this->assertTrue(\Phpcraft\UUID::fromString("fffffff1-ffff-fff1-ffff-fff1fffffff0")->isSlim());
	}

	function testCounter()
	{
		$counter = new \Phpcraft\Counter;
		$this->assertEquals(0, $counter->next());
		$this->assertEquals(1, $counter->next());
	}

	function testSlotDisplayName()
	{
		$slot = new \Phpcraft\Slot(\Phpcraft\Item::get("stone"));
		$this->assertNull($slot->getDisplayName());
		$name = ["text" => "Test", "color" => "yellow"];
		$slot->setDisplayName($name);
		$this->assertEquals($name, $slot->getDisplayName());
		$con = new \Phpcraft\Connection(47);
		$con->writeSlot($slot);
		$this->assertTrue(strpos($con->write_buffer, "§eTest") !== false);
		$con->read_buffer = $con->write_buffer;
		$this->assertEquals($name, $con->readSlot()->getDisplayName());
		$this->assertEquals("", $con->read_buffer);
	}

	function testEntityMetadata()
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
		$this->assertEquals("", $con->read_buffer);
		$this->assertTrue($read_metadata->burning);
		$this->assertFalse($read_metadata->crouching);
		$this->assertFalse($read_metadata->elytraing);
		$this->assertEquals($metadata->custom_name, $read_metadata->custom_name);
		$this->assertNull($read_metadata->silent);
	}

	function testBlockMaterial()
	{
		$this->assertNotNull($grass = \Phpcraft\BlockMaterial::get("grass_block"));
		$this->assertEquals(2 << 4, $grass->getId(47));
		$this->assertEquals(9, $grass->getId(404));
	}
}
