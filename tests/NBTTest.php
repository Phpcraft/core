<?php
require_once __DIR__."/../vendor/autoload.php";
final class NBTTest extends \PHPUnit\Framework\TestCase
{
	function testReadAndWriteListCompoundAndInt()
	{
		$bin = "\x09\x00\x04List\x0A\x00\x00\x00\x01\x03\x00\x03Int\xFF\xFF\xFF\xFF\x00";
		$con = new \Phpcraft\Connection();
		$con->read_buffer = $bin;
		$list = $con->readNBT();
		$this->assertEquals("", $con->read_buffer);
		$this->assertTrue($list instanceof \Phpcraft\NbtList);
		$this->assertEquals("List", $list->name);
		$this->assertEquals(1, count($list->children));
		$compound = $list->children[0];
		$this->assertTrue($compound instanceof \Phpcraft\NbtCompound);
		$this->assertEquals("", $compound->name);
		$this->assertEquals(1, count($compound->children));
		$int = $compound->children[0];
		$this->assertTrue($int instanceof \Phpcraft\NbtInt);
		$this->assertEquals("Int", $int->name);
		$this->assertEquals(-1, $int->value);
		$list->write($con);
		$this->assertEquals($bin, $con->write_buffer);
	}

	function testNbtBigTest()
	{
		$bin = file_get_contents(__DIR__."/bigtest.nbt");
		$con = new \Phpcraft\Connection(-1);
		$con->read_buffer = $bin;
		$tag = $con->readNBT();
		$this->assertEquals("", $con->read_buffer);
		$this->assertTrue($tag instanceof \Phpcraft\NbtCompound);
		$this->assertEquals("Level", $tag->name);
		$tag->write($con);
		$this->assertEquals($bin, $con->write_buffer);
	}
}
