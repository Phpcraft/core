<?php
namespace Phpcraft;
class ServerboundBrandPluginMessagePacket extends ServerboundPluginMessagePacket
{
	/**
	 * @param string $data The brand.
	 */
	public function __construct(string $data = "")
	{
		parent::__construct("brand", $data);
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