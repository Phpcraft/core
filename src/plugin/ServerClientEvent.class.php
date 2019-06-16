<?php
namespace Phpcraft;
abstract class ServerClientEvent extends ServerEvent
{
	/**
	 * The client that has triggered this event.
	 *
	 * @var ClientConnection $client
	 */
	public $client;

	/**
	 * @param Server $server
	 * @param ClientConnection $client
	 */
	public function __construct(Server $server, ClientConnection $client)
	{
		parent::__construct($server);
		$this->client = $client;
	}
}