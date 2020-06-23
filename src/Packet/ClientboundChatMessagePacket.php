<?php
namespace Phpcraft\Packet;
use Phpcraft\
{ChatComponent, Connection, Enum\ChatPosition};
use hellsh\UUID;
/**
 * @since 0.5.23
 */
class ClientboundChatMessagePacket extends Packet
{
	/**
	 * @var ChatComponent $chat
	 */
	public $chat;
	/**
	 * @var int $position
	 */
	public $position;

	function __construct(ChatComponent $chat, int $position = ChatPosition::SYSTEM)
	{
		$this->chat = $chat;
		$this->position = $position;
	}

	static function read(Connection $con)
	{
		$packet = new ClientboundChatMessagePacket($con->readChat(), $con->readByte());
		if($con->protocol_version >= 701)
		{
			$con->readUUID();
		}
		return $packet;
	}

	function send(Connection $con): void
	{
		$con->writeString(json_encode($this->chat->toArray()));
		$con->writeByte($this->position);
		if($con->protocol_version >= 701)
		{
			$con->writeUUID(UUID::getNullUuid());
		}
	}

	function __toString()
	{
		return "{ClientboundChatMessagePacket: ".(ChatPosition::nameOf($this->position) ?? "Position {$this->position}").", ".json_encode($this->chat->toArray())."}";
	}
}
