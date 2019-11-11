<?php
namespace Phpcraft\Command;
use Phpcraft\Server;
interface ServerCommandSender extends CommandSender
{
	/**
	 * @return Server
	 */
	function getServer(): Server;
}
