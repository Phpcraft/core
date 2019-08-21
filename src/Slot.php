<?php
namespace Phpcraft;
use Phpcraft\Nbt\
{NbtCompound, NbtEnd, NbtString, NbtTag};
class Slot
{
	const HEAD = 5;
	const CHEST = 6;
	const LEGS = 7;
	const FEET = 8;
	const HOTBAR_1 = 36;
	const HOTBAR_2 = 37;
	const HOTBAR_3 = 38;
	const HOTBAR_4 = 39;
	const HOTBAR_5 = 40;
	const HOTBAR_6 = 41;
	const HOTBAR_7 = 42;
	const HOTBAR_8 = 43;
	const HOTBAR_9 = 44;
	const OFF_HAND = 45;
	/**
	 * The item in this slot.
	 *
	 * @var Item $item
	 */
	public $item;
	/**
	 * How many times the item is in this slot.
	 *
	 * @var integer $count
	 */
	public $count;
	/**
	 * The NBT data of the item in this slot.
	 *
	 * @var NbtTag $nbt
	 */
	public $nbt;

	/**
	 * The construct.
	 *
	 * @param Item $item The item in this slot.
	 * @param integer $count How many times the item is in this slot.
	 * @param NbtTag $nbt The NBT data of the item in this slot.
	 */
	function __construct(Item $item = null, int $count = 1, NbtTag $nbt = null)
	{
		$this->item = $item;
		$this->count = $count;
		$this->nbt = $nbt;
	}

	/**
	 * Returns the display name of the item in this slot as a chat object or null if not set.
	 *
	 * @return array|null
	 */
	function getDisplayName()
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

	function getNBT(): NbtTag
	{
		return $this->nbt == null ? new NbtEnd() : $this->nbt;
	}

	/**
	 * Sets the NBT of the Slot based on the given SNBT string.
	 *
	 * @param string $snbt
	 * @return Slot $this
	 */
	function setSNBT(string $snbt): Slot
	{
		$this->nbt = NbtTag::fromSNBT($snbt);
		return $this;
	}

	/**
	 * Sets the display name of the item in this slot.
	 *
	 * @param array $name The new display name; chat object, or null to clear.
	 * @return Slot $this
	 */
	function setDisplayName(array $name): Slot
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

	function __toString()
	{
		return Slot::toString($this);
	}

	static function toString($slot)
	{
		if(Slot::isEmpty($slot))
		{
			return "{Slot: Empty}";
		}
		assert($slot instanceof Slot);
		$str = "{Slot: {$slot->count}x {$slot->item->name}";
		if($slot->hasNBT())
		{
			$str .= ", NBT ".$slot->nbt->__toString();
		}
		return $str."}";
	}

	/**
	 * @param Slot|null $slot
	 * @return boolean
	 */
	static function isEmpty($slot)
	{
		return $slot == null || $slot->item == null || $slot->count < 1 || $slot->count > 64;
	}

	/**
	 * @return boolean
	 */
	function hasNBT()
	{
		return $this->nbt != null && !($this->nbt instanceof NbtEnd);
	}
}
