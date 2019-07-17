<?php
namespace Phpcraft;
use Phpcraft\Exception\IOException;
class EntityBase extends EntityMetadata
{
	/**
	 * @var boolean $burning
	 */
	public $burning = null;
	/**
	 * @var boolean $crouching
	 */
	public $crouching = null;
	/**
	 * @var boolean $sprinting
	 */
	public $sprinting = null;
	/**
	 * @var boolean $swimming
	 */
	public $swimming = null;
	/**
	 * @var boolean $invisible
	 */
	public $invisible = null;
	/**
	 * @var boolean $glowing
	 */
	public $glowing = null;
	/**
	 * @var boolean $elytraing
	 */
	public $elytraing = null;
	/**
	 * Custom name of the entity; chat object.
	 *
	 * @var array $custom_name
	 */
	public $custom_name = null;
	/**
	 * @var boolean $silent
	 */
	public $silent = null;

	/**
	 * Writes this non-null metadata values to the Connection's write buffer.
	 *
	 * @param Connection $con
	 */
	function write(Connection $con)
	{
		if($this->burning !== null || $this->crouching !== null || $this->sprinting !== null || $this->invisible !== null)
		{
			$byte = 0;
			if($this->burning)
			{
				$byte += 0x01;
			}
			if($this->crouching)
			{
				$byte += 0x02;
			}
			if($this->sprinting)
			{
				$byte += 0x08;
			}
			if($this->swimming && $con->protocol_version >= 358)
			{
				$byte += 0x10;
			}
			if($this->invisible)
			{
				$byte += 0x20;
			}
			if($this->glowing && $con->protocol_version >= 49)
			{
				$byte += 0x40;
			}
			if($this->elytraing && $con->protocol_version >= 77)
			{
				$byte += 0x80;
			}
			self::writeByte($con, 0, $byte);
		}
		if($this->silent !== null)
		{
			self::writeBoolean($con, 4, $this->silent);
		}
		if($this->custom_name !== null)
		{
			if($con->protocol_version >= 57)
			{
				self::writeOptChat($con, 2, $this->custom_name);
			}
			else
			{
				if(!empty($this->custom_name))
				{
					self::writeString($con, 2, Phpcraft::chatToText($this->custom_name, 2));
				}
				else
				{
					self::writeString($con, 2, "");
				}
			}
		}
		if(get_called_class() == __CLASS__)
		{
			self::finish($con);
		}
	}

	function getStringAttributes()
	{
		$attr = [];
		if($this->custom_name !== null)
		{
			array_push($attr, "\"".Phpcraft::chatToText($this->custom_name)."\"");
		}
		if($this->burning !== null)
		{
			array_push($attr, ($this->burning ? "" : "Not ")."Burning");
		}
		if($this->crouching !== null)
		{
			array_push($attr, ($this->crouching ? "" : "Not ")."Crouching");
		}
		if($this->sprinting !== null)
		{
			array_push($attr, ($this->sprinting ? "" : "Not ")."Sprinting");
		}
		if($this->swimming !== null)
		{
			array_push($attr, ($this->swimming ? "" : "Not ")."Swimming");
		}
		if($this->invisible !== null)
		{
			array_push($attr, ($this->invisible ? "" : "Not ")."Invisible");
		}
		if($this->glowing !== null)
		{
			array_push($attr, ($this->glowing ? "" : "Not ")."Glowing");
		}
		if($this->elytraing !== null)
		{
			array_push($attr, ($this->elytraing ? "" : "Not ")."Elytraing");
		}
		if($this->silent !== null)
		{
			array_push($attr, ($this->silent ? "" : "Not ")."Silent");
		}
		return $attr;
	}

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
			case 0:
				$byte = $con->readByte();
				$this->elytraing = false;
				if($byte >= 0x80)
				{
					if($con->protocol_version >= 77)
					{
						$this->elytraing = true;
					}
					$byte -= 0x80;
				}
				$this->glowing = false;
				if($byte >= 0x40)
				{
					if($con->protocol_version >= 49)
					{
						$this->glowing = false;
					}
					$byte -= 0x40;
				}
				if($byte >= 0x20)
				{
					$this->invisible = true;
					$byte -= 0x20;
				}
				else
				{
					$this->invisible = false;
				}
				$this->swimming = false;
				if($byte >= 0x10)
				{
					if($con->protocol_version >= 358)
					{
						$this->swimming = true;
					}
					$byte -= 0x10;
				}
				if($byte >= 0x08)
				{
					$this->sprinting = true;
					$byte -= 0x08;
				}
				else
				{
					$this->sprinting = false;
				}
				if($byte >= 0x04)
				{
					$byte -= 0x04;
				}
				if($byte >= 0x02)
				{
					$this->crouching = true;
					$byte -= 0x02;
				}
				else
				{
					$this->crouching = false;
				}
				if($byte >= 0x01)
				{
					$this->burning = true;
				}
				else
				{
					$this->burning = false;
				}
				return true;
			case 2:
				if($con->protocol_version >= 57)
				{
					$this->custom_name = $con->readBoolean() ? $con->readChat() : null;
				}
				else
				{
					$name = $con->readString();
					if($name == "")
					{
						$this->custom_name = null;
					}
					else
					{
						$this->custom_name = Phpcraft::textToChat($name);
					}
				}
				return true;
			case 4:
				$this->silent = $con->readBoolean();
				return true;
		}
		return false;
	}
}
