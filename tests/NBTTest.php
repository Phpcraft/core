<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\
{Connection, NBT\CompoundTag, NBT\IntTag, NBT\ListTag};
class NBTTest
{
	function testReadAndWriteListCompoundAndInt()
	{
		$bin = "\x09\x00\x04List\x0A\x00\x00\x00\x01\x03\x00\x03Int\xFF\xFF\xFF\xFF\x00";
		$con = new Connection();
		$con->read_buffer = $bin;
		$list = $con->readNBT();
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
		Nose::assert($list instanceof ListTag);
		Nose::assertEquals("List", $list->name);
		Nose::assertEquals(1, count($list->children));
		$compound = $list[0];
		Nose::assert($compound instanceof CompoundTag);
		Nose::assertEquals("", $compound->name);
		Nose::assertEquals(1, count($compound->children));
		$int = $compound["Int"];
		Nose::assert($int instanceof IntTag);
		Nose::assertEquals("Int", $int->name);
		Nose::assert(gmp_cmp($int->value, -1) == 0);
		$list->write($con);
		Nose::assertEquals($bin, $con->write_buffer);
	}

	function testNbtBigTest()
	{
		$bin = file_get_contents(__DIR__."/bigtest.nbt");
		$con = new Connection(-1);
		$con->read_buffer = $bin;
		$tag = $con->readNBT();
		Nose::assertEquals($con->read_buffer_offset, strlen($con->read_buffer));
		Nose::assert($tag instanceof CompoundTag);
		Nose::assertEquals("Level", $tag->name);
		$tag->write($con);
		Nose::assertEquals($bin, $con->write_buffer);
		Nose::assertEquals($tag->toSNBT(true), file_get_contents(__DIR__."/bigtest.snbt"));
	}
}
