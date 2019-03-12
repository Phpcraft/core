<?php
namespace Phpcraft;
class EntityLiving extends EntityBase
{
	/**
	 * @var float $health
	 */
	public $health = null;

	protected function read_(\Phpcraft\Connection $con, $index)
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
	 * @copydoc EntityMetadata::write
	 */
	function write(\Phpcraft\Connection $con)
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
}
