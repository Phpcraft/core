<?php
/**
 * This plugin provides clients with the /help command.
 * I swear to God, if you remove this and leave players clueless about what commands they have available to them I will strangle you in your sleep.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Command\CommandSender, Plugin, PluginManager};
if(PluginManager::$command_prefix == ".")
{
	$this->unregister();
	return;
}
$this->registerCommand([
	"help",
	"?"
], function(CommandSender &$sender)
{
	$commands = [];
	foreach(PluginManager::$registered_commands as $command)
	{
		if($command->isUsableBy($sender))
		{
			array_push($commands, $command->getSyntax());
		}
	}
	$sender->sendMessage(["text" => "You have access to ".count($commands)." commands:\n".join("\n", $commands)]);
});
