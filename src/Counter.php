<?php
namespace Phpcraft;
use GMP;
class Counter
{
	/**
	 * @var GMP $i
	 */
	protected $i;

	function __construct()
	{
		$this->i = gmp_init(-1);
	}

	function current(): GMP
	{
		return $this->i;
	}

	function next(): GMP
	{
		$this->i = gmp_add($this->i, 1);
		return $this->i;
	}
}
