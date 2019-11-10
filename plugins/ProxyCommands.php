<?php
/**
 * The plugin provides the /proxy:connect and /proxy:disconnect commands to the Phpcraft proxy.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Account, ClientConnection, Command\CommandSender, Connection, Event\ProxyConnectEvent, Phpcraft, Plugin, PluginManager, ServerConnection, Versions};
if(PluginManager::$command_prefix != "/proxy:")
{
	$this->unregister();
	return;
}
$this->registerCommand("connect", function(ClientConnection $sender, string $address, string $account = "")
{
	$join_specs = [];
	if($account != "")
	{
		$sender->sendMessage("Resolving username...");
		$json = json_decode(file_get_contents("https://apimon.de/mcuser/".$account), true);
		if(!$json || !$json["id"])
		{
			$sender->sendMessage([
				"text" => "Error: Minecraft account not found.",
				"color" => "red"
			]);
			return;
		}
		$account = new Account($account);
		$join_specs = [
			"1.1.1.1",
			$json["id"]
		];
	}
	else
	{
		global $account;
		if(!$account)
		{
			$sender->sendMessage([
				"text" => "The proxy is not logged in. Please provide an account name to connect to an offline mode or BungeeCord-compatible server.",
				"color" => "red"
			]);
			return;
		}
	}
	global $server_con, $transform_packets;
	if($server_con instanceof ServerConnection)
	{
		$server_con->close();
		$server_con = null;
		$sender->sendMessage("Disconnected.");
	}
	$sender->sendMessage("Resolving hostname...");
	$server = Phpcraft::resolve($address);
	$serverarr = explode(":", $server);
	if(count($serverarr) != 2)
	{
		$sender->sendMessage([
			"text" => "Error: Got {$server}",
			"color" => "red"
		]);
		return;
	}
	$sender->sendMessage("Connecting to {$server}...");
	$stream = @fsockopen($serverarr[0], intval($serverarr[1]), $errno, $errstr, 3);
	if(!$stream)
	{
		$sender->sendMessage([
			"text" => $errstr,
			"color" => "red"
		]);
		return;
	}
	$sender->sendMessage("Getting version information from {$server}...");
	$server_con = new ServerConnection($stream, $sender->protocol_version);
	$server_con->sendHandshake($serverarr[0], intval($serverarr[1]), Connection::STATE_STATUS);
	$server_con->writeVarInt(0x00); // Status Request
	$server_con->send();
	$packet_id = $server_con->readPacket();
	if($packet_id !== 0x00)
	{
		$sender->sendMessage([
			"text" => "Server answered status request with packet id ".$packet_id,
			"color" => "red"
		]);
		$server_con->close();
		return;
	}
	$json = json_decode($server_con->readString(), true);
	$server_con->close();
	if(empty($json) || empty($json["version"]) || empty($json["version"]["protocol"]))
	{
		$sender->sendMessage([
			"text" => "Invalid status response: ".json_encode($json),
			"color" => "red"
		]);
		return;
	}
	if($json["version"]["protocol"] == $sender->protocol_version)
	{
		$sender->sendMessage([
			"text" => "Server supports ".Versions::protocolToRange($sender->protocol_version).".",
			"color" => "green"
		]);
		$transform_packets = false;
	}
	else
	{
		$sender->sendMessage([
			"text" => "Server doesn't support ".Versions::protocolToRange($sender->protocol_version).", suggests using ".Versions::protocolToRange($json["version"]["protocol"]).".",
			"color" => "yellow"
		]);
		if(!Versions::protocolSupported($json["version"]["protocol"]))
		{
			$sender->sendMessage([
				"text" => "Phpcraft will probably not be able to transform the server's packets for you.",
				"color" => "red"
			]);
			return;
		}
		$sender->sendMessage("Phpcraft will transform supported packets for you.");
		$transform_packets = true;
	}
	$sender->sendMessage("Connecting to {$server} (for real this time)...");
	$stream = @fsockopen($serverarr[0], intval($serverarr[1]), $errno, $errstr, 3);
	if(!$stream)
	{
		$sender->sendMessage([
			"text" => $errstr,
			"color" => "red"
		]);
		return;
	}
	$sender->sendMessage("Logging in...");
	$server_con = new ServerConnection($stream, $sender->protocol_version);
	$server_con->sendHandshake($serverarr[0], intval($serverarr[1]), Connection::STATE_LOGIN, $join_specs);
	if($error = $server_con->login($account))
	{
		$sender->sendMessage([
			"text" => $error,
			"color" => "red"
		]);
		return;
	}
	$sender->sendMessage("Connected and logged in.");
	PluginManager::fire(new ProxyConnectEvent($sender, $server_con));
})
	 ->registerCommand("disconnect", function(CommandSender $sender)
	 {
		 global $server_con;
		 if($server_con instanceof ServerConnection)
		 {
			 $server_con->close();
			 $server_con = null;
		 }
		 $sender->sendMessage("Disconnected.");
	 });