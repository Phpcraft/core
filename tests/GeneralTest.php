<?php
require_once __DIR__."/../vendor/autoload.php";
final class GeneralTest extends \PHPUnit\Framework\TestCase
{
	function testAddHyphensToUuid()
	{
		$this->assertEquals("e0603b59-2edc-45f7-acc7-b0cccd6656e1", \Phpcraft\Phpcraft::addHypensToUUID("e0603b592edc45f7acc7b0cccd6656e1"));
		$this->assertEquals("e0603b59-2edc-45f7-acc7-b0cccd6656e1", \Phpcraft\Phpcraft::addHypensToUUID("e0603b59-2edc-45f7-acc7-b0cccd6656e1"));
	}

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
	}
}
