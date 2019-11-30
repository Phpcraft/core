<?php
/** @noinspection PhpComposerExtensionStubsInspection */
echo "Phpcraft Proxy Server\n\n";
if(empty($argv))
{
	die("This is for PHP-CLI. Connect to your server via SSH and use `php proxy.php`.\n");
}
require "vendor/autoload.php";
use pas\
{pas, stdin};
use Phpcraft\
{Account, ClientConnection, Event\ProxyJoinEvent, PluginManager, ProxyServer, Versions};
echo "Would you like to provide an account to be possesed? [y/N] ";
stdin::init(null, false);
if(stdin::getNextLine() == "y")
{
	$account = Account::cliLogin();
	echo "Authenticated as {$account->username}.\n";
}
$server = new ProxyServer("Phpcraft Proxy", [
	"groups" => [
		"default" => [
			"allow" => "everything"
		]
	]
], null, null);
echo "Loading plugins...\n";
PluginManager::$command_prefix = "/proxy:";
PluginManager::loadPlugins();
echo "Loaded ".count(PluginManager::$loaded_plugins)." plugin(s).\n";
$server->join_function = function(ClientConnection $con) use (&$account, &$server)
{
	if(!Versions::protocolSupported($con->protocol_version))
	{
		$con->disconnect(["text" => "You're using an incompatible version."]);
		return;
	}
	if(PluginManager::fire(new ProxyJoinEvent($server, $con)))
	{
		$con->close();
		return;
	}
	$server->connectToIntegratedServer($con);
	$con->startPacket("clientbound_chat_message");
	if($account instanceof Account)
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. This proxy is authenticated as '.$account->username.'. Use /proxy:connect <ip> to connect to a Minecraft server."}');
	}
	else
	{
		$con->writeString('{"text":"Welcome to this Phpcraft proxy, '.$con->username.'. Use /proxy:connect <ip> <username> to connect to a reverse proxy-compatible server."}');
	}
	$con->writeByte(1);
	$con->send();
};
pas::loop();
echo "Proxy is not listening on any ports and has no clients, so it's shutting down.\n";
