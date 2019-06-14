<?php
namespace Phpcraft;
use hellsh\Enum;
abstract class Dimension extends Enum
{
	const OVERWORLD = 0;
	const NETHER = -1;
	const END = 1;
}
