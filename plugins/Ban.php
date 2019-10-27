<?php
/**
 * My dad works at Microsoft and he's gonna get you banned from this server.
 * Wait, Microsoft actually owns Minecraft now.
 * Shit.
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConfiguration, ClientConnection, Command\CommandSender, Command\GreedyString, Event\Event, Event\ServerJoinEvent, Plugin, PluginManager};
if(PluginManager::$command_prefix != "/")
{
	$this->unregister();
	return;
}
$this->on(function(ServerJoinEvent $e)
{
	$ban_reason = $e->client->config->get("ban");
	if($ban_reason !== null)
	{
		$e->client->disconnect([
			"text" => $ban_reason === true ? "You have been banned from this server." : $ban_reason
		]);
		$e->cancelled = true;
	}
}, Event::PRIORITY_HIGHEST)
	 ->registerCommand("ban", function(CommandSender &$sender, ClientConfiguration $victim, GreedyString $reason = null)
	 {
		 if($sender instanceof ClientConnection && $sender->config === $victim)
		 {
			 $sender->sendMessage("Silly you.");
			 return;
		 }
		 if($victim->hasPermission("unbannable"))
		 {
			 $sender->sendMessage([
				 "text" => "You can't ban the unbannable ".$victim->getName().". I can't believe you even tried.",
				 "color" => "red"
			 ]);
			 return;
		 }
		 $victim->set("ban", $reason ? $reason->value : true);
		 if($victim->isOnline())
		 {
			 $victim->getPlayer()
					->disconnect("You have been banned from this server".($reason ? ": ".$reason->value : "."));
		 }
		 $sender->sendAdminBroadcast([
			 "text" => $victim->getName()." has been banned.".($reason === null ? " And you didn't even need a reason, apparently." : ""),
			 "color" => "yellow"
		 ], "use /ban");
	 }, "use /ban")
	 ->registerCommand([
		 "unban",
		 "pardon"
	 ], function(CommandSender &$sender, ClientConfiguration $victim)
	 {
		 if($victim->has("ban"))
		 {
			 $victim->unset("ban");
			 $sender->sendAdminBroadcast([
				 "text" => $victim->getName()." has been unbanned.",
				 "color" => "green"
			 ], "use /unban");
		 }
		 else
		 {
			 $sender->sendMessage([
				 "text" => $victim->getName()." is not banned. Better safe than sorry?",
				 "color" => "red"
			 ]);
		 }
	 }, "use /unban");
