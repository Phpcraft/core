<?php
namespace Phpcraft\Entity;
class Insentient extends Living
{
	static function getOffset(int $protocol_version): int
	{
		if($protocol_version >= 565)
		{
			return 15;
		}
		else if($protocol_version >= 472)
		{
			return 14;
		}
		else if($protocol_version >= 57)
		{
			return 12;
		}
		else
		{
			return 16;
		}
	}
	// TODO: No AI & Left-handed
}
