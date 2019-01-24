<?php
namespace Phpcraft;
/** The template for the keep alive request and response packets. */
abstract class KeepAlivePacket extends Packet
{
	/**
	 * The identifier of the keep alive packet.
	 * @var integer $keepAliveId
	 */
	protected $keepAliveId;

	/**
	 * The constructor.
	 * @param string $name The name of the packet.
	 * @param integer $keepAliveId The identifier of the keep alive packet.
	 */
	protected function __construct($name, $keepAliveId)
	{
		parent::__construct($name);
		if($keepAliveId == null)
		{
			$this->keepAliveId = time();
		}
		else
		{
			$this->keepAliveId = $keepAliveId;
		}
	}

	/**
	 * Returns the identifier of the keep alive packet.
	 * @return integer
	 */
	function getKeepAliveId()
	{
		return $this->keepAliveId;
	}

	/**
	 * Called by children when Packet::read() is being called.
	 * @param Connection $con
	 */
	protected function _read(\Phpcraft\Connection $con)
	{
		if($con->protocol_version >= 339)
		{
			$this->keepAliveId = $con->readLong();
		}
		else
		{
			$this->keepAliveId = $con->readVarInt();
		}
		return $this;
	}

	/**
	 * @copydoc Packet::send
	 */
	function send(\Phpcraft\Connection $con)
	{
		$con->startPacket($this->name);
		if($con->protocol_version >= 339)
		{
			$con->writeLong($this->keepAliveId);
		}
		else
		{
			$con->writeVarInt($this->keepAliveId);
		}
		$con->send();
	}
}
