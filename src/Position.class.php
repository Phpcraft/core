<?php
namespace Phpcraft;
class Position
{
	/**
	 * @var double $x
	 */
	public $x = 0;
	/**
	 * @var double $y
	 */
	public $y = 0;
	/**
	 * @var double $z
	 */
	public $z = 0;

	function __construct($x = 0, $y = 0, $z = 0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	function toString()
	{
		return "{Position ".$this->x." ".$this->y." ".$this->z."}";
	}
}
