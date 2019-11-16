<?php
/**
 * This plugin adds the /group command so clients' permissions can be adjusted easily.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConfiguration, Command\ServerCommandSender, Plugin};
$this->registerCommand("group", function(ServerCommandSender &$sender, ClientConfiguration $player, string $group = "")
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
