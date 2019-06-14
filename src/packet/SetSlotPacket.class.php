<?php
namespace Phpcraft;
class SetSlotPacket extends Packet
{
	/**
	 * The ID of the window being updated. 0 for inventory.
	 * @var integer $window
	 */
	public $window = 0;
	/**
	 * The ID of the slot being updated.
	 * @var integer $slotId
	 * @see https://wiki.vg/Inventory
	 */
	public $slotId = 0;
	/**
	 * The new value of the slot.
	 * @var Slot $slot
	 */
	public $slot = null;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 * @param Connection $con
	 * @return SetSlotPacket
	 * @throws IOException
	 */
	public static function read(Connection $con)
	{
		$packet = new SetSlotPacket();
		$packet->window = $con->readByte();
		$packet->slotId = gmp_intval($con->readShort());
		$packet->slot = $con->readSlot();
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 * @param Connection $con
	 * @throws IOException
	 * @throws MissingMetadataException
	 */
	public function send(Connection $con)
	{
		$con->startPacket("set_slot");
		$con->writeByte($this->window);
		$con->writeShort($this->slotId);
		$con->writeSlot($this->slot);
		$con->send();
	}

	public function __toString()
	{
		return "{Set Slot: Window ID {$this->window}, Slot ID {$this->slotId}, ".Slot::toString($this->slot)."}";
	}
}
