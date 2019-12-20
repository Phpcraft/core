<?php
namespace Phpcraft\Entity;
use Phpcraft\
{Connection, Exception\IOException};
class Creeper extends Monster
{
	/**
	 * @var bool|null $charged
	 */
	public $charged = null;

	/**
	 * Writes non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 * @return void
	 */
	function write(Connection $con): void
	{
		parent::write($con);
		if($this->charged !== null)
		{
			self::writeBoolean($con, ($con->protocol_version >= 57 ? ($con->protocol_version >= 472 ? 15 : 13) : 17), $this->charged);
		}
		if(get_called_class() == __CLASS__)
		{
			self::finish($con);
		}
	}

	/**
	 * @return array<string>
	 */
	function getStringAttributes(): array
	{
		$attr = parent::getStringAttributes();
		if($this->charged !== null)
		{
			array_push($attr, ($this->charged ? "" : "Not ")."Charged");
		}
		return $attr;
	}

	/**
	 * @param Connection $con
	 * @param int $index
	 * @return boolean
	 * @throws IOException
	 */
	protected function read_(Connection $con, int $index): bool
	{
		switch($index)
		{
			case ($con->protocol_version >= 57 ? ($con->protocol_version >= 472 ? 15 : 13) : 17):
				$this->charged = $con->readBoolean();
				return true;
		}
		return parent::read_($con, $index);
	}
}
