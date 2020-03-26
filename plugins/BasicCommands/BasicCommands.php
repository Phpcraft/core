<?php
/**
 * This plugin provides clients with the /abilities, /gamemode, and /metadata commands.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Command\GreedyString, Command\ServerCommandSender, Plugin, PluginManager};
require "GamemodeArgument.php";
require "GamemodeArgumentProvider.php";
if(PluginManager::$command_prefix == ".")
{
	$this->unregister();
	return;
}
$this->registerCommand("me", function(ServerCommandSender &$sender, GreedyString $action)
{
	$sender->getServer()
		   ->broadcast("* ".$sender->getName()." ".$action->value);
}, "use /me")
	 ->registerCommand([
		 "gamemode",
		 "gm"
	 ], function(ClientConnection &$client, GamemodeArgument $gamemode)
	 {
		 $client->setGamemode($gamemode->gamemode);
		 $client->startPacket("player_info");
		 $client->writeVarInt(1);
		 $client->writeVarInt(1);
		 $client->writeUUID($client->uuid);
		 $client->writeVarInt($gamemode->gamemode);
		 $client->send();
	 }, "use /gamemode")
	 ->registerCommand("noclipcreative", function(ClientConnection &$client)
	 {
		 $client->setGamemode(1, false);
		 $client->startPacket("player_info");
		 $client->writeVarInt(1);
		 $client->writeVarInt(1);
		 $client->writeUUID($client->uuid);
		 $client->writeVarInt(3);
		 $client->send();
		 $client->instant_breaking = $client->can_fly = $client->flying = $client->invulnerable = true;
		 $client->sendAbilities();
	 }, "use /noclipcreative")
	 ->registerCommand("abilities", function(ClientConnection &$client, $abilities)
	 {
		 $client->startPacket("clientbound_abilities");
		 $client->writeByte(hexdec($abilities));
		 $client->writeFloat(0.05);
		 $client->writeFloat(0.1);
		 $client->send();
	 }, "use /abilities")
	 ->registerCommand("metadata", function(ClientConnection &$client, $metadata)
	 {
		 $client->startPacket("entity_metadata");
		 $client->writeVarInt($client->eid);
		 $client->writeByte(0);
		 $client->writeVarInt(0);
		 $client->writeByte(hexdec($metadata));
		 $client->writeByte(0xFF);
		 $client->send();
	 }, "use /metadata")
	 ->registerCommand("elytra", function(ClientConnection &$client)
	 {
		 $client->startPacket("entity_metadata");
		 $client->writeVarInt($client->eid);
		 $client->writeByte(0);
		 $client->writeVarInt(0);
		 $client->writeByte(0x80);
		 $client->writeByte(0xFF);
		 $client->send();
	 }, "use /metadata");
