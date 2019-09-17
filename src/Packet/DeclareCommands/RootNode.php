<?php
namespace Phpcraft\Packet\DeclareCommands;
class RootNode extends Node
{
	const TYPE_ID = 0;

	function __toString()
	{
		$str = "{RootNode";
		if($this->children)
		{
			$str .= ":";
			foreach($this->children as $child)
			{
				$str .= " ".$child;
			}
		}
		return $str."}";
	}
}
