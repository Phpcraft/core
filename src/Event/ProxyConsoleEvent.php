<?php
namespace Phpcraft\Event;
use hotswapp\CancellableEvent;
use Phpcraft\ProxyServer;
/** The event emitted by the proxy when the console has proposed a broadcast. Cancellable. */
class ProxyConsoleEvent extends ProxyEvent
{
	use CancellableEvent;
	/**
	 * The message that the console has proposed.
	 *
	 * @var string $message
	 */
	public $message;

	function __construct(ProxyServer $server, string $message)
	{
		parent::__construct($server);
		$this->message = $message;
	}
}
