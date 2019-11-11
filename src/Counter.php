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

	/**
	 * @return GMP
	 */
	function current(): GMP
	{
		return $this->i;
	}

	/**
	 * @return GMP
	 */
	function next(): GMP
	{
		$this->i = gmp_add($this->i, 1);
		return $this->i;
	}
}
