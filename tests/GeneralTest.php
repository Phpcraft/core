<?php
/** @noinspection PhpUnhandledExceptionInspection */
require __DIR__."/../vendor/autoload.php";
use Phpcraft\
{Material, Connection, Counter, EntityBase, EntityLiving, Item, Phpcraft, Slot, UUID, Versions};
class GeneralTest
{
	function testTextToChat()
	{
		Nose::assertEquals([
			"text" => "&1Test",
			"color" => "black"
		], Phpcraft::textToChat("§0&1Test", false));
		Nose::assertEquals(["text" => "&r&0Test"], Phpcraft::textToChat("&r&0Test", false));
		Nose::assertEquals([
			"text" => "Test",
			"color" => "black"
		], Phpcraft::textToChat("&r&0Test", true));
	}

	function testChatToText()
	{
		Nose::assertEquals("Test", Phpcraft::chatToText([
			"text" => "Test",
			"color" => "black"
		], 0));
		Nose::assertEquals("§r§0Test", Phpcraft::chatToText([
			"text" => "Test",
			"color" => "black"
		], 2));
		Nose::assertEquals("&r&0Test", Phpcraft::chatToText([
			"text" => "Test",
			"color" => "black"
		], 3));
		Nose::assertEquals('<span style="color:#000">Test</span>', Phpcraft::chatToText([
			"text" => "Test",
			"color" => "black"
		], 4));
	}

	function testUuid()
	{
		$uuid = new UUID("e0603b592edc45f7acc7b0cccd6656e1");
		Nose::assertEquals($uuid->toInt(), "963689993953");
		Nose::assertFalse((new UUID("fffffff0-ffff-fff0-ffff-fff0fffffff0"))->isSlim());
		Nose::assertFalse((new UUID("fffffff0-ffff-fff0-ffff-fff1fffffff1"))->isSlim());
		Nose::assertFalse((new UUID("fffffff0-ffff-fff1-ffff-fff0fffffff1"))->isSlim());
		Nose::assertFalse((new UUID("fffffff0-ffff-fff1-ffff-fff1fffffff0"))->isSlim());
		Nose::assertFalse((new UUID("fffffff1-ffff-fff0-ffff-fff0fffffff1"))->isSlim());
		Nose::assertFalse((new UUID("fffffff1-ffff-fff0-ffff-fff1fffffff0"))->isSlim());
		Nose::assertFalse((new UUID("fffffff1-ffff-fff1-ffff-fff0fffffff0"))->isSlim());
		Nose::assertFalse((new UUID("fffffff1-ffff-fff1-ffff-fff1fffffff1"))->isSlim());
		Nose::assertTrue((new UUID("fffffff0-ffff-fff0-ffff-fff0fffffff1"))->isSlim());
		Nose::assertTrue((new UUID("fffffff0-ffff-fff0-ffff-fff1fffffff0"))->isSlim());
		Nose::assertTrue((new UUID("fffffff0-ffff-fff1-ffff-fff0fffffff0"))->isSlim());
		Nose::assertTrue((new UUID("fffffff0-ffff-fff1-ffff-fff1fffffff1"))->isSlim());
		Nose::assertTrue((new UUID("fffffff1-ffff-fff0-ffff-fff0fffffff0"))->isSlim());
		Nose::assertTrue((new UUID("fffffff1-ffff-fff0-ffff-fff1fffffff1"))->isSlim());
		Nose::assertTrue((new UUID("fffffff1-ffff-fff1-ffff-fff0fffffff1"))->isSlim());
		Nose::assertTrue((new UUID("fffffff1-ffff-fff1-ffff-fff1fffffff0"))->isSlim());
	}

	function testVersions()
	{
		Nose::assertEquals(["1.12.2"], Versions::protocolToMinecraft(340));
		Nose::assertEquals([
			"1.10.2",
			"1.10.1",
			"1.10"
		], Versions::protocolToMinecraft(210));
		Nose::assertEquals("1.8 - 1.8.9", Versions::protocolToRange(47));
		Nose::assert(array_key_exists("1.9", Versions::releases()));
		Nose::assertFalse(array_key_exists("1.9-pre1", Versions::releases()));
		Nose::assertFalse(array_key_exists("16w07a", Versions::releases()));
		Nose::assert(Versions::protocolSupported(47));
		Nose::assertFalse(Versions::protocolSupported(46));
	}

	function testCounter()
	{
		$counter = new Counter();
		Nose::assertEquals(0, $counter->next());
		Nose::assertEquals(1, $counter->next());
	}

	function testSlotDisplayName()
	{
		$slot = Item::get("stone")->slot();
		Nose::assertNull($slot->getDisplayName());
		$name = [
			"text" => "Test",
			"color" => "yellow"
		];
		$slot->setDisplayName($name);
		Nose::assertEquals($name, $slot->getDisplayName());
		$con = new Connection(47);
		$con->writeSlot($slot);
		Nose::assert(strpos($con->write_buffer, "§eTest") !== false);
		$con->read_buffer = $con->write_buffer;
		Nose::assertEquals($name, $con->readSlot()
									  ->getDisplayName());
		Nose::assertEquals("", $con->read_buffer);
	}

	function testEntityMetadata()
	{
		$metadata = new EntityBase();
		$metadata->burning = true;
		$metadata->elytraing = true;
		$metadata->custom_name = [
			"text" => "Test",
			"color" => "yellow"
		];
		$con = new Connection(47);
		$metadata->write($con);
		Nose::assert(strpos($con->write_buffer, "§eTest") !== false);
		$con->read_buffer = $con->write_buffer;
		$read_metadata = (new EntityBase())->read($con);
		assert($read_metadata instanceof EntityLiving);
		Nose::assertEquals("", $con->read_buffer);
		Nose::assert($read_metadata->burning);
		Nose::assertFalse($read_metadata->crouching);
		Nose::assertFalse($read_metadata->elytraing);
		Nose::assertEquals($metadata->custom_name, $read_metadata->custom_name);
		Nose::assertNull($read_metadata->silent);
	}

	function testBlockMaterial()
	{
		Nose::assertNotNull($grass = Material::get("grass_block"));
		Nose::assertEquals(2 << 4, $grass->getId(47));
		Nose::assertEquals(9, $grass->getId(404));
	}
}
