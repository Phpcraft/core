<?php
/**
 * Displays join and leave messages when players join and leave.
 * Don't want this functionality? Just throw this plugin in the <del>bin</del> "_disabled" folder! The wonders of writing a server from scratch!
 *
 * @var Plugin $this
 */
use Phpcraft\
{Event\ServerJoinEvent, Event\ServerLeaveEvent, Plugin};
use hotswapp\Event;
$this->on(function(ServerJoinEvent $event)
{
	if($event->cancelled)
	{
		return;
	}
	$event->server->broadcast([
		"color" => "yellow",
		"translate" => "multiplayer.player.joined",
		"with" => [
			[
				"text" => $event->client->username
			]
		]
	]);
}, Event::PRIORITY_LOWEST);
$this->on(function(ServerLeaveEvent $event)
{
	$event->server->broadcast([
		"color" => "yellow",
		"translate" => "multiplayer.player.left",
		"with" => [
			[
				"text" => $event->client->username
			]
		]
	]);
}, Event::PRIORITY_LOWEST);
