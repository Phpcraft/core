<?php /** @noinspection PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\ChatComponent;
class ChatComponentTest
{
	function testToArray()
	{
		Nose::assertEquals(ChatComponent::text("Hello, world!")
										->toArray(), ["text" => "Hello, world!"]);
		Nose::assertEquals(ChatComponent::text("§rHello, world!")
										->toArray(), ["text" => "Hello, world!"]);
		Nose::assertEquals(ChatComponent::text("§lHello, world!")
										->toArray(), [
			"text" => "Hello, world!",
			"bold" => true
		]);
		Nose::assertEquals(ChatComponent::text("Hello, §lworld!")
										->toArray(), [
			"text" => "Hello, ",
			"extra" => [
				[
					"text" => "world!",
					"bold" => true
				]
			]
		]);
		Nose::assertEquals(ChatComponent::text("Hello, §lworld§r!")
										->toArray(), [
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
		Nose::assertEquals(ChatComponent::text("Hello, &lworld&r!", true)
										->toArray(), [
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

	function testToString()
	{
		$chat = ChatComponent::text("Test")
							 ->black();
		Nose::assertEquals("Test", $chat->toString(ChatComponent::FORMAT_NONE));
		Nose::assertEquals("\e[30mTest\e[m", $chat->toString(ChatComponent::FORMAT_ANSI));
		Nose::assertEquals("§0Test", $chat->toString(ChatComponent::FORMAT_SILCROW));
		Nose::assertEquals("&0Test", $chat->toString(ChatComponent::FORMAT_AMPERSAND));
		Nose::assertEquals('<span style="color:#000">Test</span>', $chat->toString(ChatComponent::FORMAT_HTML));
		$chat = ChatComponent::text("A")
							 ->red()
							 ->add(ChatComponent::text("B")
												->yellow())
							 ->add(ChatComponent::text("C"));
		Nose::assertEquals($chat->toString(ChatComponent::FORMAT_SILCROW), "§cA§eB§cC");
	}
}
