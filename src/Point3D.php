<?php
namespace Phpcraft;
/** A point in three-dimensional space, or a three-dimensional vector. Whatever you want it to be. */
class Point3D
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

	function __construct(float $x = 0, float $y = 0, float $z = 0)
	{
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	function add(Point3D $b): Point3D
	{
		return new Point3D($this->x + $b->x, $this->y + $b->y, $this->z + $b->z);
	}

	function multiply(Point3D $b): Point3D
	{
		return new Point3D($this->x * $b->x, $this->y * $b->y, $this->z * $b->z);
	}

	function distance(Point3D $dest): float
	{
		return sqrt(pow($this->x - $dest->x, 2) + pow($this->y - $dest->y, 2) + pow($this->z - $dest->z, 2));
	}

	function forward(int $distance, float $yaw, float $pitch): Point3D
	{
		$x = (pi() / 180 * ($yaw - 90));
		$factor = 100 / 90 * (90 - abs($pitch)) / 100 * $distance;
		return $this->subtract(new Point3D(cos($x) * $factor, 100 / 90 * $pitch / 100 * $distance, sin($x) * $factor));
	}

	function subtract(Point3D $b): Point3D
	{
		return new Point3D($this->x - $b->x, $this->y - $b->y, $this->z - $b->z);
	}

	function floor(): Point3D
	{
		return new Point3D(floor($this->x), floor($this->y), floor($this->z));
	}

	function round(): Point3D
	{
		return new Point3D(round($this->x), round($this->y), round($this->z));
	}

	function ceil(): Point3D
	{
		return new Point3D(ceil($this->x), ceil($this->y), ceil($this->z));
	}

	/**
	 * Floors all axes and adds 0.5.
	 *
	 * @return Point3D
	 */
	function block(): Point3D
	{
		return new Point3D(floor($this->x) + 0.5, floor($this->y) + 0.5, floor($this->z) + 0.5);
	}

	function equals(Point3D $b): bool
	{
		return $this->x == $b->x && $this->y == $b->y && $this->z == $b->z;
	}

	function __toString()
	{
		return "{Point3D: ".$this->x." ".$this->y." ".$this->z."}";
	}
}
