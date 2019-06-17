<?php
namespace Phpcraft\Enum;
use hellsh\Enum;
abstract class Gamemode extends Enum
{
	const SURVIVAL = 0;
	const CREATIVE = 1;
	const ADVENTURE = 2;
	const SPECTATOR = 3;
}
