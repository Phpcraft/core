<?php
namespace Phpcraft;
class Slot
{
	const ID_HEAD = 5;
	const ID_CHEST = 6;
	const ID_LEGS = 7;
	const ID_FEET = 8;
	const ID_HOTBAR_1 = 36;
	const ID_HOTBAR_2 = 37;
	const ID_HOTBAR_3 = 38;
	const ID_HOTBAR_4 = 39;
	const ID_HOTBAR_5 = 40;
	const ID_HOTBAR_6 = 41;
	const ID_HOTBAR_7 = 42;
	const ID_HOTBAR_8 = 43;
	const ID_HOTBAR_9 = 44;
	const ID_OFF_HAND = 45;

	/**
	 * The item in this slot.
	 * @var Item $item
	 */
	public $item;
	/**
	 * How many times the item is in this slot.
	 * @var integer $count
	 */
	public $count;
	/**
	 * The NBT data of the item in this slot.
	 * @var NbtTag $nbt
	 */
	public $nbt;

	/**
	 * The construct.
	 * @param Item $item The item in this slot.
	 * @param integer $count How many times the item is in this slot.
	 * @param NbtTag $nbt The NBT data of the item in this slot.
	 */
	public function __construct(Item $item = null, $count = 1, NbtTag $nbt = null)
	{
		$this->item = $item;
		$this->count = $count;
		$this->nbt = $nbt;
	}

	/**
	 * Returns the display name of the item in this slot as a chat object or null if not set.
	 * @return array
	 */
	public function getDisplayName()
	{
		$nbt = $this->getNBT();
		if($nbt instanceof NbtCompound)
		{
			$display = $nbt->getChild("display");
			if($display && $display instanceof NbtCompound)
			{
				$name = $display->getChild("Name");
				if($name && $name instanceof NbtString)
				{
					return json_decode($name->value, true);
				}
			}
		}
		return null;
	}

	/**
	 * Sets the display name of the item in this slot.
	 * @param array $name The new display name; chat object, or null to clear.
	 * @return Slot $this
	 * @throws Exception
	 */
	public function setDisplayName($name)
	{
		$name = json_encode($name);
		$nbt = $this->getNBT();
		if(!($nbt instanceof NbtCompound))
		{
			$nbt = new NbtCompound("tag");
		}
		$display = $nbt->getChild("display");
		if(!$display || !($display instanceof NbtCompound))
		{
			array_push($nbt->children, $display = new NbtCompound("display"));
		}
		$display_name = $display->getChild("Name");
		if($display_name && $display_name instanceof NbtString)
		{
			$display_name->value = $name;
		}
		else
		{
			$display_name = new NbtString("Name", $name);
		}
		$this->nbt = $nbt->addChild($display->addChild($display_name));
		return $this;
	}

	/**
	 * @return NbtTag
	 */
	public function getNBT()
	{
		return $this->nbt == null ? new NbtEnd() : $this->nbt;
	}

	/**
	 * @return boolean
	 */
	public function hasNBT()
	{
		return $this->nbt != null && !($this->nbt instanceof NbtEnd);
	}

	/**
	 * @param Slot|null $slot
	 * @return boolean
	 */
	public static function isEmpty($slot)
	{
		return $slot == null || $slot->item == null || $slot->count < 1 || $slot->count > 64;
	}

	public static function toString($slot)
	{
		if(Slot::isEmpty($slot))
		{
			return "{Slot: Empty}";
		}
		$str = "{Slot: {$slot->count}x {$slot->item->name}";
		if($slot->hasNBT())
		{
			$str .= ", NBT ".$slot->nbt->toString();
		}
		return $str."}";
	}
}
