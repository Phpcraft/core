<?php
namespace Phpcraft;
use Phpcraft\Exception\IOException;
class ClientboundBrandPluginMessagePacket extends ClientboundPluginMessagePacket
{
	/**
	 * @param string $data The brand.
	 */
	public function __construct(string $data = "")
	{
		parent::__construct("minecraft:brand", $data);
	}

	/**
	 * @param Connection $con
	 * @throws IOException
	 */
	protected function read_(Connection $con)
	{
		$this->data = $con->readString();
	}

	protected function send_(Connection $con)
	{
		$con->writeString($this->data);
	}
}