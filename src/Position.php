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

	public function __construct(float $x = 0, float $y = 0, float $z = 0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	public function __toString()
	{
		return "{Position ".$this->x." ".$this->y." ".$this->z."}";
	}
}
