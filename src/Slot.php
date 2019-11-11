<?php
namespace Phpcraft;
use Phpcraft\NBT\
{CompoundTag, EndTag, NBT, StringTag};
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
	 * @var int $count
	 */
	public $count;
	/**
	 * The NBT data of the item in this slot.
	 *
	 * @var NBT $nbt
	 */
	public $nbt;

	/**
	 * The construct.
	 *
	 * @param Item|null $item The item in this slot.
	 * @param int $count How many times the item is in this slot.
	 * @param NBT|null $nbt The NBT data of the item in this slot.
	 */
	function __construct(?Item $item = null, int $count = 1, ?NBT $nbt = null)
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
	function getDisplayName(): ?array
	{
		if($this->nbt instanceof CompoundTag)
		{
			$display = $this->nbt->getChild("display");
			if($display && $display instanceof CompoundTag)
			{
				$name = $display->getChild("Name");
				if($name && $name instanceof StringTag)
				{
					return json_decode($name->value, true);
				}
			}
		}
		return null;
	}

	/**
	 * Sets the NBT of the Slot based on the given SNBT string.
	 *
	 * @param string $snbt
	 * @return Slot $this
	 */
	function setSNBT(string $snbt): Slot
	{
		$this->nbt = NBT::fromSNBT($snbt);
		return $this;
	}

	/**
	 * Sets the display name of the item in this slot.
	 *
	 * @param array|null $name The new display name; chat object, or null to clear.
	 * @return Slot $this
	 */
	function setDisplayName(?array $name): Slot
	{
		if(!$this->nbt instanceof CompoundTag)
		{
			$this->nbt = new CompoundTag("tag");
		}
		$display = $this->nbt->getChild("display");
		if(!$display instanceof CompoundTag)
		{
			$this->nbt->addChild($display = new CompoundTag("display"));
		}
		$display_name = $display->getChild("Name");
		if($display_name instanceof StringTag)
		{
			$display_name->value = json_encode($name);
		}
		else
		{
			$display->addChild(new StringTag("Name", json_encode($name)));
		}
		return $this;
	}

	function __toString()
	{
		return Slot::toString($this);
	}

	static function toString(?Slot $slot): string
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
	static function isEmpty(?Slot $slot): bool
	{
		return $slot == null || $slot->item == null || $slot->count < 1 || $slot->count > 64;
	}

	/**
	 * @return boolean
	 */
	function hasNBT(): bool
	{
		return $this->nbt != null && !($this->nbt instanceof EndTag);
	}
}
