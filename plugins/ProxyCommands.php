<?php
/**
 * The plugin provides the /proxy:connect and /proxy:disconnect commands to the Phpcraft proxy.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Account, ClientConnection, Command\CommandSender, Event\ProxyConnectEvent, Phpcraft, Plugin, PluginManager, ServerConnection};
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
	global $server_con;
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
	$stream = fsockopen($serverarr[0], intval($serverarr[1]), $errno, $errstr, 3);
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
	$server_con->sendHandshake($serverarr[0], intval($serverarr[1]), 2, $join_specs);
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