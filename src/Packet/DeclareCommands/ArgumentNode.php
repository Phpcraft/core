<?php
namespace Phpcraft\Packet\DeclareCommands;
use ReflectionClass;
use ReflectionException;
class ArgumentNode extends Node
{
	const TYPE_ID = 2;

	public $name;
	public $provider;

	function __construct(string $name = null, string $provider = null)
	{
		$this->name = $name;
		$this->provider = $provider;
	}

	function __toString()
	{
		$str = "{ArgumentNode \"{$this->name}\"";
		try
		{
			$str .= ", Type ".(new ReflectionClass($this->provider))->getMethod("getValue")
																	->getReturnType();
		}
		catch(ReflectionException $e)
		{
		}
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
		return $str."}";
	}
}
