<?php
namespace Phpcraft;
class Counter
{
	protected $i = -1;

	function current(): int
	{
		return $this->i;
	}

	function next(): int
	{
		return ++$this->i;
	}
}
