<?php
/**
 * This plugin provides clients of the server with /abilities, /gamemode, and /metadata.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConfiguration, ClientConnection, Command\Command, Command\CommandSender, Plugin, PluginManager};
require "GamemodeArgument.php";
require "GamemodeArgumentProvider.php";
$this->registerCommand("help", function(CommandSender &$sender)
{
	$commands = [];
	foreach(PluginManager::$registered_commands as $command)
	{
		assert($command instanceof Command);
		if($command->isUsableBy($sender))
		{
			array_push($commands, $command->getSyntax());
		}
	}
	$sender->sendMessage(["text" => "You have access to ".count($commands)." commands:\n".join("\n", $commands)]);
})
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
if(PluginManager::$command_prefix != "/proxy:")
{
	$this->registerCommand("group", function(CommandSender &$sender, ClientConfiguration $player, string $group = "")
	{
		if($group == "")
		{
			$sender->sendMessage([
				"text" => $player->getName()." is currently in group '".$player->getGroupName()."'."
			]);
		}
		else if($sender->getServer()
					   ->getGroup($group) !== null)
		{
			$player->setGroup($group);
			$sender->sendMessage([
				"text" => $player->getName()." is now in group '".$player->getGroupName()."'.",
				"color" => "green"
			]);
		}
		else
		{
			$sender->sendMessage([
				"text" => "Group '$group' does not exist.",
				"color" => "red"
			]);
		}
	}, "use /group");
}
