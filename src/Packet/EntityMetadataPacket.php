<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Entity\Base, Entity\Metadata, Exception\IOException};
use GMP;
/** Sent by the server to clients when entity metadata changes. */
class EntityMetadataPacket extends EntityPacket
{
	// TODO: Create some facility to track entity id -&gt; entity type relations so metadata beyond "Base" can be read.
	/**
	 * @var Metadata $metadata
	 */
	public $metadata;

	/**
	 * @param array<GMP>|GMP|int|string $eids A single entity ID or an array of entity IDs.
	 * @param Metadata|null $metadata
	 */
	function __construct($eids = [], Metadata $metadata = null)
	{
		parent::__construct($eids);
		$this->metadata = $metadata;
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return EntityMetadataPacket
	 * @throws IOException
	 */
	static function read(Connection $con): EntityMetadataPacket
	{
		$packet = new EntityMetadataPacket($con->readVarInt(), new Base());
		$packet->metadata->read($con);
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		foreach($this->eids as $eid)
		{
			$con->startPacket("entity_metadata");
			$con->writeVarInt($eid);
			$this->metadata->write($con);
			$con->send();
		}
	}

	function __toString()
	{
		return "{EntityMetadataPacket: Entit".(count($this->eids) == 1 ? "y" : "ies")." ".join(", ", $this->eids)." ".$this->metadata->__toString()."}";
	}
}