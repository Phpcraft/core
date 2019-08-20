<?php
/**
 * This plugin provides clients of the server with /abilities, /gamemode, and /metadata.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Command\CommandSender, Plugin\Plugin, Plugin\PluginManager};
$this->registerCommand("help", function(CommandSender &$sender)
{
	$commands = [];
	foreach(PluginManager::$registered_commands as $command)
	{
		array_push($commands, $command->getSyntax());
	}
	$sender->sendMessage(["text" => "I know the following commands:\n".join("\n", $commands)]);
})
	 ->registerCommand([
		 "gamemode",
		 "gm"
	 ], function(CommandSender &$client, int $gamemode)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 $client->setGamemode($gamemode);
		 $client->startPacket("player_info");
		 $client->writeVarInt(1);
		 $client->writeVarInt(1);
		 $client->writeUUID($client->uuid);
		 $client->writeVarInt($gamemode);
		 $client->send();
	 })
	 ->registerCommand("abilities", function(CommandSender &$client, $abilities)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 $client->startPacket("clientbound_abilities");
		 $client->writeByte(hexdec($abilities));
		 $client->writeFloat(0.05);
		 $client->writeFloat(0.1);
		 $client->send();
	 })
	 ->registerCommand("metadata", function(CommandSender &$client, $metadata)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 $client->startPacket("entity_metadata");
		 $client->writeVarInt($client->eid);
		 $client->writeByte(0);
		 $client->writeVarInt(0);
		 $client->writeByte(hexdec($metadata));
		 $client->writeByte(0xFF);
		 $client->send();
	 });
