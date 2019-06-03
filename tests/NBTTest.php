<?php
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\{Connection, NbtCompound, NbtInt, NbtList};
class NBTTest
{
	function testReadAndWriteListCompoundAndInt()
	{
		$bin = "\x09\x00\x04List\x0A\x00\x00\x00\x01\x03\x00\x03Int\xFF\xFF\xFF\xFF\x00";
		$con = new Connection();
		$con->read_buffer = $bin;
		$list = $con->readNBT();
		Nose::assertEquals("", $con->read_buffer);
		Nose::assert($list instanceof NbtList);
		Nose::assertEquals("List", $list->name);
		Nose::assertEquals(1, count($list->children));
		$compound = $list->children[0];
		Nose::assert($compound instanceof NbtCompound);
		Nose::assertEquals("", $compound->name);
		Nose::assertEquals(1, count($compound->children));
		$int = $compound->children[0];
		Nose::assert($int instanceof NbtInt);
		Nose::assertEquals("Int", $int->name);
		Nose::assertEquals(-1, $int->value);
		$list->write($con);
		Nose::assertEquals($bin, $con->write_buffer);
	}

	function testNbtBigTest()
	{
		$bin = file_get_contents(__DIR__."/bigtest.nbt");
		$con = new Connection(-1);
		$con->read_buffer = $bin;
		$tag = $con->readNBT();
		Nose::assertEquals("", $con->read_buffer);
		Nose::assert($tag instanceof NbtCompound);
		Nose::assertEquals("Level", $tag->name);
		$tag->write($con);
		Nose::assertEquals($bin, $con->write_buffer);
	}
}
