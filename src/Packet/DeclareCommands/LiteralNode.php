<?php
namespace Phpcraft\Packet\DeclareCommands;
class LiteralNode extends Node
{
	const TYPE_ID = 1;
	public $name;

	function __construct(string $name = null, Node $redirect_to = null)
	{
		$this->name = $name;
		$this->redirect_to = $redirect_to;
	}

	function __toString()
	{
		$str = "{LiteralNode \"{$this->name}\"";
		if($this->redirect_to)
		{
			$str .= ": redirects to \"{$this->redirect_to->name}\"";
		}
		else
		{
			if($this->executable)
			{
				$str .= ", Executable";
			}
			if($this->children)
			{
				$str .= ":";
				foreach($this->children as $child)
				{
					$str .= " ".$child;
				}
			}
		}
		return $str."}";
	}
}
