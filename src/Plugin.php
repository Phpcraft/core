<?php /** @noinspection PhpIncludeInspection */
namespace Phpcraft;
use DomainException;
use Phpcraft\Command\Command;
use RuntimeException;
use hotswapp\Plugin as HotswappPlugin;
class Plugin extends HotswappPlugin
{
	/**
	 * The name of the plugin.
	 *
	 * @var string $name
	 */
	public $name;
	/**
	 * @var array<array{function:Closure,priority:int}> $event_handlers
	 */
	public $event_handlers = [];

	/**
	 * Registers a command.
	 *
	 * @param string|array<string> $names One or more names without the / prefix. So, if you want a "/gamemode" comand, you provide "gamemode", and if you want a "//wand" command, you provide "/wand".
	 * @param callable $function The function called when the command is executed. The first argument will be the CommandSender but if type-hinted to be ClientConnection or Server it will be exclusive to players or the server, respectively. Further arguments determine the command's arguments, e.g. <code>-&gt;registerCommand("gamemode", function(ClientConnection &$con, int $gamemode){...})</code> would result in the player-exclusive command <code>/gamemode &lt;gamemode&gt;</code> where the gamemode argument only allows integers.
	 * @param string|null $required_permission The permission required to use this command or null if not applicable.
	 * @return Plugin $this
	 */
	protected function registerCommand($names, callable $function, ?string $required_permission = null): Plugin
	{
		if($this->unregistered)
		{
			throw new RuntimeException("Call to Plugin::on() after Plugin::unregister()");
		}
		if(is_string($names))
		{
			$names = [$names];
		}
		foreach($names as $name)
		{
			foreach(PluginManager::$registered_commands as $command)
			{
				if(in_array($name, $command->names))
				{
					throw new DomainException(PluginManager::$command_prefix.$name." is already registered");
				}
			}
		}
		PluginManager::$registered_commands->attach(new Command($this, $names, $required_permission, $function));
		return $this;
	}

	/**
	 * Unregisters the plugin, including its event handlers and its commands.
	 * Make sure your plugin has no statements other than `return;` after this.
	 *
	 * @return void
	 */
	protected function unregister(): void
	{
		parent::unregister();
		foreach(PluginManager::$registered_commands as $command)
		{
			if($command->plugin == $this)
			{
				PluginManager::$registered_commands->detach($command);
			}
		}
	}
}
