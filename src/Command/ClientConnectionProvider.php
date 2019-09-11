<?php
namespace Phpcraft\Command;
use DomainException;
use LogicException;
use Phpcraft\ClientConnection;
class ClientConnectionProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		$arg = strtolower($arg);
		if(!$sender->hasServer())
		{
			throw new LogicException("This command was only intended for servers");
		}
		foreach($sender->getServer()
					   ->getPlayers() as $player)
		{
			if(strtolower($player->username) == $arg)
			{
				$this->value = $player;
				return;
			}
		}
		throw new DomainException("Unable to find an online player named $arg");
	}

	function getValue(): ClientConnection
	{
		return $this->value;
	}
}
