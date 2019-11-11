<?php
namespace Phpcraft\Command;
use DomainException;
use LogicException;
use Phpcraft\ClientConfiguration;
class ClientConfigurationProvider extends ArgumentProvider
{
	public function __construct(CommandSender &$sender, string $arg)
	{
		if(!$sender instanceof ServerCommandSender)
		{
			throw new LogicException("This command was only intended for servers");
		}
		$this->value = $sender->getServer()
							  ->getOfflinePlayer($arg);
		if($this->value === null)
		{
			throw new DomainException("A player named $arg was never on this server");
		}
	}

	/**
	 * @return ClientConfiguration
	 */
	function getValue(): ClientConfiguration
	{
		return $this->value;
	}
}
