<?php
namespace Phpcraft\Entity;
use Phpcraft\
{Connection, Exception\IOException};
class Living extends Base
{
	/**
	 * @var float $health
	 */
	public $health = null;

	/**
	 * Writes non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 */
	function write(Connection $con)
	{
		parent::write($con);
		if($this->health !== null)
		{
			self::writeFloat($con, $con->protocol_version >= 57 ? 7 : 6, $this->health);
		}
		if(get_called_class() == __CLASS__)
		{
			self::finish($con);
		}
	}

	function getStringAttributes()
	{
		$attr = parent::getStringAttributes();
		if($this->health !== null)
		{
			array_push($attr, "Health ".$this->health);
		}
		return $attr;
	}

	/**
	 * @param Connection $con
	 * @param int $index
	 * @return boolean
	 * @throws IOException
	 */
	protected function read_(Connection $con, int $index)
	{
		switch($index)
		{
			case ($con->protocol_version >= 57 ? 7 : 6):
				$this->health = $con->readFloat();
				return true;
		}
		return parent::read_($con, $index);
	}
}
