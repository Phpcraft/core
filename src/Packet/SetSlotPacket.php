<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException, Exception\MissingMetadataException, Slot};
class SetSlotPacket extends Packet
{
	/**
	 * The ID of the window being updated. 0 for inventory.
	 *
	 * @var integer $window
	 */
	public $window;
	/**
	 * The ID of the slot being updated.
	 *
	 * @var integer $slotId
	 * @see Slot
	 * @see https://wiki.vg/Inventory
	 */
	public $slotId;
	/**
	 * The new value of the slot.
	 *
	 * @var Slot $slot
	 */
	public $slot;

	/**
	 * @param integer $window The ID of the window being updated. 0 for inventory.
	 * @param integer $slotId The ID of the slot being updated. See https://wiki.vg/Inventory and {@link Slot} constants.
	 * @param Slot $slot The new value of the slot.
	 */
	function __construct(int $window = 0, int $slotId = 0, Slot $slot = null)
	{
		$this->window = $window;
		$this->slotId = $slotId;
		$this->slot = $slot;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return SetSlotPacket
	 * @throws IOException
	 */
	static function read(Connection $con): Packet
	{
		return new SetSlotPacket($con->readByte(), gmp_intval($con->readShort()), $con->readSlot());
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 * @throws MissingMetadataException
	 */
	function send(Connection $con)
	{
		$con->startPacket("set_slot");
		$con->writeByte($this->window);
		$con->writeShort($this->slotId);
		$con->writeSlot($this->slot);
		$con->send();
	}

	function __toString()
	{
		return "{Set Slot: Window ID {$this->window}, Slot ID {$this->slotId}, ".Slot::toString($this->slot)."}";
	}
}
