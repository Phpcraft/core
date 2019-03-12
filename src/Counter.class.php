<?php
namespace Phpcraft;
class Counter
{
	protected $i = -1;

	function next()
	{
		return ++$this->i;
	}
}
