<?php
/**
 * @var Plugin $this
 */
use Phpcraft\
{ChatComponent, ClientConnection, Connection, IntegratedServer, Plugin, World\Structure};
$this->registerCommand([
	"structure",
	"loadstructure",
	"load-structure",
	"load_structure"
], function(ClientConnection $client, string $file_name)
{
	$server = $client->getServer();
	if(!$server instanceof IntegratedServer || $client->downstream !== null)
	{
		$client->sendMessage(ChatComponent::text("This server doesn't have world.")
										  ->red());
		return;
	}
	if(strpos($file_name, "/") !== false || strpos($file_name, "\\") !== false || substr($file_name, -4) != ".nbt")
	{
		$client->sendMessage(ChatComponent::text("Invalid structure file name.")
										  ->red());
		return;
	}
	$file_con = new Connection();
	$file_con->read_buffer = zlib_decode(file_get_contents($file_name));
	$structure = Structure::fromStructure($file_con);
	$client->sendMessage("That's a ".$structure->width." x ".$structure->height." x ".$structure->depth." structure coming right at ya!");
	$server->world->apply($structure, $client->pos);
}, "change the world");
