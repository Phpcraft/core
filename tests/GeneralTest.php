<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\{BlockState,Connection,Counter,Entity\Base,Entity\Living,Item,Phpcraft,Server,Versions};
class GeneralTest
{
	function testTextToChat()
	{
		Nose::assertEquals(Phpcraft::textToChat("Hello, world!"), ["text" => "Hello, world!"]);
		Nose::assertEquals(Phpcraft::textToChat("§rHello, world!"), ["text" => "Hello, world!"]);
		Nose::assertEquals(Phpcraft::textToChat("§lHello, world!"), [
			"text" => "Hello, world!",
			"bold" => true
		]);
		Nose::assertEquals(Phpcraft::textToChat("Hello, §lworld!"), [
			"text" => "Hello, ",
			"extra" => [
				[
					"text" => "world!",
					"bold" => true
				]
			]
		]);
		Nose::assertEquals(Phpcraft::textToChat("Hello, §lworld§r!"), [
			"text" => "Hello, ",
			"extra" => [
				[
					"text" => "world",
					"bold" => true
				],
				[
					"text" => "!"
				]
			]
		]);
		Nose::assertEquals(Phpcraft::textToChat("Hello, &lworld&r!", true), [
			"text" => "Hello, ",
			"extra" => [
				[
					"text" => "world",
					"bold" => true
				],
				[
					"text" => "!"
				]
			]
		]);
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

	function testVersions()
	{
		Nose::assertEquals(["1.12.2"], Versions::protocolToMinecraft(340));
		Nose::assertEquals([
			"1.10.2",
			"1.10.1",
			"1.10"
		], Versions::protocolToMinecraft(210));
		Nose::assertEquals("1.8 - 1.8.9", Versions::protocolToRange(47));
		Nose::assertTrue(array_key_exists("1.9", Versions::releases(true)));
		Nose::assertTrue(array_key_exists("1.9", Versions::releases(false)));
		Nose::assertTrue(array_key_exists("1.9-pre1", Versions::list(true)));
		Nose::assertTrue(array_key_exists("1.9-pre1", Versions::list(false)));
		Nose::assertFalse(array_key_exists("1.9-pre1", Versions::releases(true)));
		Nose::assertFalse(array_key_exists("1.9-pre1", Versions::releases(false)));
		Nose::assertTrue(array_key_exists("16w07a", Versions::list(true)));
		Nose::assertTrue(array_key_exists("16w07a", Versions::list(false)));
		Nose::assertFalse(array_key_exists("16w07a", Versions::releases(true)));
		Nose::assertFalse(array_key_exists("16w07a", Versions::releases(false)));
		Nose::assertTrue(Versions::protocolSupported(47));
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
		$slot = Item::get("stone")
					->slot();
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
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
	}

	function testEntityMetadata()
	{
		$metadata = new Base();
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
		$read_metadata = (new Base())->read($con);
		assert($read_metadata instanceof Living);
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
		Nose::assertTrue($read_metadata->burning);
		Nose::assertFalse($read_metadata->crouching);
		Nose::assertFalse($read_metadata->elytraing);
		Nose::assertEquals($metadata->custom_name, $read_metadata->custom_name);
		Nose::assertNull($read_metadata->silent);
	}

	function testBlockState()
	{
		Nose::assertNotNull($grass = BlockState::get("grass_block"));
		Nose::assertEquals($grass, BlockState::get("grass_block[snowy=false]"));
		Nose::assertEquals($grass->getId(47), 2 << 4);
		Nose::assertEquals($grass->getId(404), 9);
	}

	function testPermissions()
	{
		$server = new Server();
		$server->setGroups([
			"default" => [
				"allow" => "use /help"
			],
			"user" => [
				"inherit" => "default",
				"allow" => "use /gamemode"
			],
			"admin" => [
				"allow" => "everything"
			]
		]);
		Nose::assertTrue($server->getGroup("default")
								->hasPermission("use /help"));
		Nose::assertFalse($server->getGroup("default")
								 ->hasPermission("use /gamemode"));
		Nose::assertFalse($server->getGroup("default")
								 ->hasPermission("use /something"));
		Nose::assertTrue($server->getGroup("user")
								->hasPermission("use /help"));
		Nose::assertTrue($server->getGroup("user")
								->hasPermission("use /gamemode"));
		Nose::assertFalse($server->getGroup("user")
								 ->hasPermission("use /something"));
		Nose::assertTrue($server->getGroup("admin")
								->hasPermission("use /something"));
	}
}
