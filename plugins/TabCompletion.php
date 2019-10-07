<?php
/**
 * Provides clients with the ability to tab-complete commands.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Command\Command, Event\ProxyJoinEvent, Event\ServerJoinEvent, Packet\DeclareCommands\DeclareCommandsPacket, Plugin, PluginManager};
$this->on(function(ProxyJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	if($event->client->protocol_version >= 393 && substr(PluginManager::$command_prefix, 0, 1) == "/")
	{
		$packet = new DeclareCommandsPacket();
		$prefix = substr(PluginManager::$command_prefix, 1);
		foreach(PluginManager::$registered_commands as $command)
		{
			assert($command instanceof Command);
			if($command->isUsableBy($event->client))
			{
				$packet->addCommand($command, $prefix);
			}
		}
		$packet->send($event->client);
	}
})
	 ->on(function(ServerJoinEvent $event)
	 {
		 /**
		  * @var Plugin $this
		  */
		 $this->fire(new ProxyJoinEvent($event->client));
	 });
