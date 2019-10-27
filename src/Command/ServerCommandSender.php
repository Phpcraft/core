<?php
namespace Phpcraft\Command;
use Phpcraft\Server;
interface ServerCommandSender extends CommandSender
{
	function getServer(): Server;
}
