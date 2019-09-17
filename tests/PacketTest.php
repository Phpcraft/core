<?php /** @noinspection PhpUnused PhpUnhandledExceptionInspection */
require_once __DIR__."/../vendor/autoload.php";
use Phpcraft\
{Command\SingleWordStringProvider, Connection, Packet\ClientboundPacket, Packet\DeclareCommands\ArgumentNode, Packet\DeclareCommands\DeclareCommandsPacket, Packet\DeclareCommands\LiteralNode};
class PacketTest
{
	function testWriteAndReadDeclareCommandsPacket()
	{
		$packet = new DeclareCommandsPacket();
		$gamemode = new LiteralNode("gamemode");
		$arg = new ArgumentNode("gamemode_arg", SingleWordStringProvider::class);
		$arg->executable = true;
		$gamemode->children = [$arg];
		$gm = new LiteralNode("gm", $gamemode);
		$packet->root_node->children = [
			$gamemode, $gm
		];
		$con = new Connection(404);
		$packet->send($con);
		$con->read_buffer = $con->write_buffer;
		$packet_id = ClientboundPacket::getById(gmp_intval($con->readVarInt()), 404);
		Nose::assertEquals($packet_id->getClass(), DeclareCommandsPacket::class);
		$packet = DeclareCommandsPacket::read($con);
		Nose::assertEquals(count($packet->root_node->children), 2);
		$gamemode = $packet->root_node->children[0];
		Nose::assert($gamemode instanceof LiteralNode);
		Nose::assert($gamemode->name == "gamemode");
		$gm = $packet->root_node->children[1];
		Nose::assert($gm instanceof LiteralNode);
		Nose::assert($gm->name == "gm");
		Nose::assertEquals($gm->redirect_to, $gamemode);
		Nose::assertEquals(count($gamemode->children), 1);
		$arg = $gamemode->children[0];
		Nose::assert($arg instanceof ArgumentNode);
		Nose::assertEquals($arg->name, "gamemode_arg");
		Nose::assertTrue($arg->executable);
		Nose::assertEquals($arg->provider, SingleWordStringProvider::class);
	}
}
