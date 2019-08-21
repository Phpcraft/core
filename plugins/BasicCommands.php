<?php
/**
 * This plugin provides clients of the server with /abilities, /gamemode, and /metadata.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Command\ArgumentProvider, Command\CommandSender, Enum\Gamemode, Plugin\Plugin, Plugin\PluginManager};
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
		private $value;

		public function __construct(string $arg)
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
		array_push($commands, $command->getSyntax());
	}
	$sender->sendMessage(["text" => "I know the following commands:\n".join("\n", $commands)]);
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
