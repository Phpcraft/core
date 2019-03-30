<?php
namespace Phpcraft;
class EntityLiving extends EntityBase
{
	/**
	 * @var float $health
	 */
	public $health = null;

	/**
	 * @param Connection $con
	 * @param integer $index
	 * @return boolean
	 * @throws IOException
	 */
	protected function read_(Connection $con, int $index)
	{
		switch($index)
		{
			case 6:
			if($con->protocol_version < 57)
			{
				$this->health = $con->readFloat();
				return true;
			}
			break;
			case 7:
			if($con->protocol_version >= 57)
			{
				$this->health = $con->readFloat();
				return true;
			}
			break;
		}
		return parent::read_($con, $index);
	}

	/**
	 * Writes this non-null metadata values to the Connection's write buffer.
	 * @param Connection $con
	 * @throws IOException
	 */
	public function write(Connection $con)
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

	public function getStringAttributes()
	{
		$attr = parent::getStringAttributes();
		if($this->health !== null)
		{
			array_push($attr, "Health ".$this->health);
		}
		return $attr;
	}
}
