<?php
use Phpcraft\
{Command\ArgumentProvider, Command\CommandSender, Enum\Gamemode};
if(!class_exists("GamemodeArgumentProvider"))
{
	class GamemodeArgumentProvider extends ArgumentProvider
	{
		public function __construct(/** @noinspection PhpUnusedParameterInspection */ CommandSender &$sender, string $arg)
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

		/**
		 * @return GamemodeArgument
		 */
		function getValue(): GamemodeArgument
		{
			return new GamemodeArgument($this->value);
		}
	}
}
