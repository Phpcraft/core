<?php
namespace Phpcraft;
class Counter
{
	protected $i = -1;

	public function next()
	{
		return ++$this->i;
	}
}
