<?php
namespace Phpcraft;
use InvalidArgumentException;
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

	/**
	 * @param Point3D|int|float|string $arg Point3D or numeric
	 * @return Point3D
	 */
	function multiply($arg): Point3D
	{
		if($arg instanceof Point3D)
		{
			return new Point3D($this->x * $arg->x, $this->y * $arg->y, $this->z * $arg->z);
		}
		if(is_numeric($arg))
		{
			return new Point3D($this->x * $arg, $this->y * $arg, $this->z * $arg);
		}
		throw new InvalidArgumentException("Expected argument to Point3D::multiply to be numeric or an instance of Point3D");
	}

	function distance(Point3D $dest): float
	{
		return sqrt(pow($this->x - $dest->x, 2) + pow($this->y - $dest->y, 2) + pow($this->z - $dest->z, 2));
	}

	function getUnitVector(float $yaw, float $pitch): Point3D
	{
		$x = pi() / 180 * $yaw;
		$y = pi() / 180 * $pitch;
		return new Point3D(-cos($y) * sin($x), -sin($y), cos($y) * cos($x));
	}

	/**
	 * @param Point3D|float $other_point3d_or_x
	 * @param float $y
	 * @param float $z
	 * @return Point3D
	 */
	function add($other_point3d_or_x, float $y = null, float $z = null): Point3D
	{
		if($other_point3d_or_x instanceof Point3D)
		{
			return new Point3D($this->x + $other_point3d_or_x->x, $this->y + $other_point3d_or_x->y, $this->z + $other_point3d_or_x->z);
		}
		if($z !== null && $y !== null)
		{
			return new Point3D($this->x + $other_point3d_or_x, $this->y + $y, $this->z + $z);
		}
		throw new InvalidArgumentException("add expects (Point3D) or (float, float, float)");
	}

	/**
	 * @param Point3D $b
	 * @return float[] Yaw & pitch
	 */
	function lookAt(Point3D $b): array
	{
		$dx = $b->x - $this->x;
		$dy = $b->y - $this->y;
		$dz = $b->z - $this->z;
		$yaw = -atan2($dx, $dz) / pi() * 180;
		$pitch = @-asin($dy / sqrt($dx * $dx + $dy * $dy + $dz * $dz)) / pi() * 180;
		return [
			$yaw < 0 ? $yaw + 360 : $yaw,
			is_nan($pitch) ? 0 : $pitch
		];
	}

	/**
	 * @param Point3D|float $other_point3d_or_x
	 * @param float $y
	 * @param float $z
	 * @return Point3D
	 */
	function subtract($other_point3d_or_x, float $y = null, float $z = null): Point3D
	{
		if($other_point3d_or_x instanceof Point3D)
		{
			return new Point3D($this->x - $other_point3d_or_x->x, $this->y - $other_point3d_or_x->y, $this->z - $other_point3d_or_x->z);
		}
		if($z !== null && $y !== null)
		{
			return new Point3D($this->x - $other_point3d_or_x, $this->y - $y, $this->z - $z);
		}
		throw new InvalidArgumentException("add expects (Point3D) or (float, float, float)");
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
	 * Floors all axes and adds 0.5 to the X & Z axes.
	 *
	 * @return Point3D
	 */
	function block(): Point3D
	{
		return new Point3D(floor($this->x) + 0.5, floor($this->y), floor($this->z) + 0.5);
	}

	/**
	 * Floors and adds 0.5 to all axes.
	 *
	 * @return Point3D
	 */
	function blockCenter(): Point3D
	{
		return new Point3D(floor($this->x) + 0.5, floor($this->y) + 0.5, floor($this->z) + 0.5);
	}

	function equals(Point3D $b): bool
	{
		return $this->x == $b->x && $this->y == $b->y && $this->z == $b->z;
	}

	function invert(): Point3D
	{
		return new Point3D($this->x * -1, $this->y * -1, $this->z * -1);
	}

	function __toString()
	{
		return "{Point3D: ".$this->x." ".$this->y." ".$this->z."}";
	}
}
