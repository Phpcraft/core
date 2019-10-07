<?php
/**
 * Crashes clients when they say "crash me"
 *
 * @var Plugin $this
 */
use Phpcraft\
{ClientConnection, Event\ServerChatEvent, Packet\JoinGamePacket, Plugin};
$this->on(function(ServerChatEvent $event)
{
	if(!$event->cancelled && $event->message == "crash me")
	{
		$con = $event->client;
		assert($con instanceof ClientConnection);
		echo $con->username." requested a crash\n";
		if($con->protocol_version < 315)
		{
			$con->startPacket("set_slot");
			$con->writeByte(0);
			$con->writeShort(36);
			$con->writeShort(0);
			$con->writeByte(0);
			$con->writeShort(0);
			$con->writeByte(0);
			$con->send();
			$con->startPacket("spawn_player");
			$con->writeVarInt(1338);
			for($i = 0; $i < 16; $i++)
			{
				$con->writeByte(0);
			}
			$con->writeInt(0);
			$con->writeInt(50);
			$con->writeInt(0);
			$con->writeByte(0);
			$con->writeByte(0);
			$con->writeShort(-1);
			$con->writeByte(127);
			$con->send();
		}
		$con->startPacket("change_game_state");
		$con->writeUnsignedByte(7);
		$con->writeFloat(1337);
		$con->send();
		$packet = new JoinGamePacket();
		$packet->dimension = 1337;
		$packet->send($con);
		$event->cancelled = true;
	}
});
