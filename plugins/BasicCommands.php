<?php
/**
 * This plugin provides clients of the server with /abilities, /gamemode, and /metadata.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConfiguration, ClientConnection, Command\ArgumentProvider, Command\Command, Command\CommandSender, Enum\Gamemode, Plugin, PluginManager};
if(!class_exists("GamemodeArgument"))
{
	class GamemodeArgument
	{
		public $gamemode;

		function __construct(int $gamemode)
		{
			$this->gamemode = $gamemode;
		}
	}

	class GamemodeArgumentProvider extends ArgumentProvider
	{
		public function __construct(CommandSender &$sender, string $arg)
		{
			if(is_numeric($arg) && $arg >= 0 && $arg <= 4)
			{
				$this->value = $arg;
			}
			else if(Gamemode::validateName(strtoupper($arg)))
			{
				$this->value = Gamemode::valueOf(strtoupper($arg));
			}
			else
			{
				throw new DomainException("Invalid gamemode: ".$arg);
			}
		}

		function getValue(): GamemodeArgument
		{
			return new GamemodeArgument($this->value);
		}
	}
}
$this->registerCommand("help", function(CommandSender &$sender)
{
	$commands = [];
	foreach(PluginManager::$registered_commands as $command)
	{
		assert($command instanceof Command);
		if($command->hasPermission($sender))
		{
			array_push($commands, $command->getSyntax());
		}
	}
	$sender->sendMessage(["text" => "You have access to ".count($commands)." commands:\n".join("\n", $commands)]);
})
	 ->registerCommand([
		 "gamemode",
		 "gm"
	 ], function(CommandSender &$client, GamemodeArgument $gamemode)
	 {
		 if(!$client instanceof ClientConnection)
		 {
			 $client->sendMessage("This command is only for players.");
			 return;
		 }
		 $client->setGamemode($gamemode->gamemode);
		 $client->startPacket("player_info");
		 $client->writeVarInt(1);
		 $client->writeVarInt(1);
		 $client->writeUUID($client->uuid);
		 $client->writeVarInt($gamemode->gamemode);
		 $client->send();
	 }, "use /gamemode")
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
	 }, "use /abilities")
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
	 }, "use /metadata")
	 ->registerCommand("group", function(CommandSender &$sender, ClientConfiguration $player, string $group = "")
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
				 "text" => "Group '".$player->getGroupName()."' does not exist.",
				 "color" => "red"
			 ]);
		 }
	 }, "use /group");
