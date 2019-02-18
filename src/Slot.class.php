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
	 * @var ItemMaterial $item
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
	function __construct(\Phpcraft\Item $item = null, $count = 1, \Phpcraft\NbtTag $nbt = null)
	{
		$this->item = $item;
		$this->count = $count;
		$this->nbt = $nbt;
	}

	static function isEmpty($slot)
	{
		return $slot == null || $slot->item == null || $slot->count < 1 || $slot->count > 64;
	}

	function getNBT()
	{
		return $this->nbt == null ? new \Phpcraft\NbtEnd() : $this->nbt;
	}

	function hasNBT()
	{
		return $this->nbt != null && !($this->nbt instanceof \Phpcraft\NbtEnd);
	}
}
