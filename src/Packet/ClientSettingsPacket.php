<?php
namespace Phpcraft\Packet;
use Phpcraft\
{Connection, Exception\IOException};
/** Sent by the client when joining and when settings have been changed. */
class ClientSettingsPacket extends Packet
{
	/**
	 * @var string $locale
	 */
	public $locale = "en_GB";
	/**
	 * @var int $render_distance
	 */
	public $render_distance = 2;
	/**
	 * Defines the messages the client accepts:
	 * <ul>
	 * <li>0: All</li>
	 * <li>1: Only system</li>
	 * <li>2: None</li>
	 * </ul>
	 *
	 * @var int $chat_mode
	 */
	public $chat_mode = 0;
	/**
	 * @var bool $chat_colors_enabled
	 */
	public $chat_colors_enabled = true;
	/**
	 * @var bool $cape_enabled
	 */
	public $cape_enabled = true;
	/**
	 * @var bool $jacket_enabled
	 */
	public $jacket_enabled = true;
	/**
	 * @var bool $left_sleeve_enabled
	 */
	public $left_sleeve_enabled = true;
	/**
	 * @var bool $right_sleeve_enabled
	 */
	public $right_sleeve_enabled = true;
	/**
	 * @var bool $left_pants_leg_enabled
	 */
	public $left_pants_leg_enabled = true;
	/**
	 * @var bool $right_pants_leg_enabled
	 */
	public $right_pants_leg_enabled = true;
	/**
	 * @var bool $hat_enabled
	 */
	public $hat_enabled = true;
	/**
	 * @var bool $left_handed
	 */
	public $left_handed = false;

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return ClientSettingsPacket
	 * @throws IOException
	 */
	static function read(Connection $con): ClientSettingsPacket
	{
		$packet = new ClientSettingsPacket();
		$packet->locale = $con->readString();
		$packet->render_distance = gmp_intval($con->readVarInt());
		$packet->chat_mode = gmp_intval($con->readVarInt());
		$packet->chat_colors_enabled = $con->readBoolean();
		$skin_flags = $con->readByte();
		$packet->hat_enabled = !($skin_flags & 0x40);
		$packet->right_pants_leg_enabled = !($skin_flags & 0x20);
		$packet->left_pants_leg_enabled = !($skin_flags & 0x10);
		$packet->right_sleeve_enabled = !($skin_flags & 0x08);
		$packet->left_sleeve_enabled = !($skin_flags & 0x04);
		$packet->jacket_enabled = !($skin_flags & 0x02);
		$packet->cape_enabled = !($skin_flags & 0x01);
		if($con->protocol_version > 47)
		{
			$packet->left_handed = ($con->readVarInt() == 0);
		}
		return $packet;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and, if the connection has a stream, sends it over the wire.
	 *
	 * @param Connection $con
	 * @throws IOException
	 */
	function send(Connection $con)
	{
		$con->startPacket("client_settings");
		$con->writeString($this->locale);
		$con->writeVarInt($this->render_distance);
		$con->writeVarInt($this->chat_mode);
		$con->writeBoolean($this->chat_colors_enabled);
		$skin_flags = 0;
		if($this->cape_enabled)
		{
			$skin_flags |= 0x01;
		}
		if($this->jacket_enabled)
		{
			$skin_flags |= 0x02;
		}
		if($this->left_sleeve_enabled)
		{
			$skin_flags |= 0x04;
		}
		if($this->right_sleeve_enabled)
		{
			$skin_flags |= 0x08;
		}
		if($this->left_pants_leg_enabled)
		{
			$skin_flags |= 0x10;
		}
		if($this->right_pants_leg_enabled)
		{
			$skin_flags |= 0x20;
		}
		if($this->hat_enabled)
		{
			$skin_flags |= 0x40;
		}
		$con->writeByte($skin_flags);
		$con->writeVarInt($this->left_handed ? 0 : 1);
		$con->send();
	}

	function __toString()
	{
		// {ClientSettingsPacket: Locale en_GB, Render Distance 16, Chat Mode }
		$str = "{ClientSettingsPacket: Locale {$this->locale}, Render Distance {$this->render_distance}, ".($this->left_handed ? "Left" : "Right")."-handed";
		switch($this->chat_mode)
		{
			case 0:
				break;
			case 1:
				$str .= ", System-only chat";
				break;
			case 2:
				$str .= ", Chat disabled";
				break;
			default:
				$str .= ", Chat mode ".$this->chat_mode;
		}
		if(!$this->chat_colors_enabled && $this->chat_mode != 2)
		{
			$str .= ", Chat colors disabled";
		}
		if(!$this->cape_enabled)
		{
			$str .= ", Cape disabled";
		}
		if(!$this->jacket_enabled)
		{
			$str .= ", Jacket disabled";
		}
		if(!$this->left_sleeve_enabled && !$this->right_sleeve_enabled)
		{
			$str .= ", Sleeves disabled";
		}
		else
		{
			if(!$this->left_sleeve_enabled)
			{
				$str .= ", Left sleeve disabled";
			}
			if(!$this->right_sleeve_enabled)
			{
				$str .= ", Right sleeve disabled";
			}
		}
		if(!$this->left_pants_leg_enabled && !$this->right_pants_leg_enabled)
		{
			$str .= ", Pants disabled";
		}
		else
		{
			if(!$this->left_pants_leg_enabled)
			{
				$str .= ", Left pants leg disabled";
			}
			if(!$this->right_pants_leg_enabled)
			{
				$str .= ", Right pants leg disabled";
			}
		}
		if(!$this->hat_enabled)
		{
			$str .= ", Hat disabled";
		}
		return $str."}";
	}
}
