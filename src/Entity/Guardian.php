<?php
namespace Phpcraft\Entity;
use GMP;
use Phpcraft\Connection;
use Phpcraft\Exception\IOException;
class Guardian extends Monster
{
	/**
	 * The ID of the entity being targeted by the guardian. 0 = no entity.
	 *
	 * @var GMP|string|int|null $target_eid
	 */
	public $target_eid = null;

	/**
	 * Writes non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 */
	function write(Connection $con)
	{
		parent::write($con);
		if($this->target_eid !== null)
		{
			self::writeInt($con, ($con->protocol_version >= 57 ? ($con->protocol_version >= 472 ? 15 : 13) : 17), $this->target_eid);
		}
		if(get_called_class() == __CLASS__)
		{
			self::finish($con);
		}
	}

	function getStringAttributes()
	{
		$attr = parent::getStringAttributes();
		if($this->target_eid !== null)
		{
			array_push($attr, "Targeting entity ".$this->target_eid);
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
		if($con->protocol_version >= 57)
		{
			switch($index)
			{
				case ($con->protocol_version >= 472 ? 15 : 13):
					$this->target_eid = $con->readVarInt();
					return true;
			}
		}
		else
		{
			switch($index)
			{
				case 17:
					$this->target_eid = $con->readVarInt();
					return true;
			}
		}
		return parent::read_($con, $index);
	}
}
