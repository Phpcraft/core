<?php
namespace Phpcraft\Command;
use DomainException;
use Exception;
use Phpcraft\
{ClientConnection, Plugin, PluginManager, Server, ServerConnection};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use RuntimeException;
class Command
{
	static private $argument_providers;
	static private $last_declared_classes = [];
	/**
	 * @var Plugin $plugin
	 */
	public $plugin;
	/**
	 * @var array<string> $names
	 */
	public $names;
	/**
	 * @var string|null $required_sender_class
	 */
	public $required_sender_class;
	/**
	 * @var string|null $required_permission
	 */
	public $required_permission;
	/**
	 * @var array<ReflectionParameter> $params
	 */
	public $params;
	/**
	 * @var callable $function
	 */
	private $function;

	function __construct(Plugin $plugin, array $names, $required_permission, callable $function)
	{
		$this->plugin = $plugin;
		$this->names = [];
		foreach($names as $name)
		{
			array_push($this->names, strtolower($name));
		}
		$this->required_permission = $required_permission;
		$this->function = $function;
		try
		{
			$params = (new ReflectionFunction($this->function))->getParameters();
			if(count($params) > 0)
			{
				$type = $params[0]->getType();
				if($type !== null)
				{
					/** @noinspection PhpDeprecationInspection */
					$type_name = $type->isBuiltin() ? "" : ($type instanceof ReflectionNamedType ? $type->getName() : $type->__toString());
					if(!in_array($type_name, [
						CommandSender::class,
						ServerCommandSender::class,
						ClientConnection::class,
						ServerConnection::class,
						Server::class
					]))
					{
						throw new DomainException(PluginManager::$command_prefix.$this->getCanonicalName()."'s first parameter's type should be CommandSender, ServerCommandSender, ClientConnection, ServerConnection, Server, or not restricted");
					}
					if($type_name != CommandSender::class)
					{
						$this->required_sender_class = $type_name;
					}
				}
			}
			$classes = get_declared_classes();
			if($new_classes = array_diff($classes, self::$last_declared_classes))
			{
				if(self::$argument_providers === null)
				{
					self::$argument_providers = [];
				}
				foreach($new_classes as $class)
				{
					if(is_subclass_of($class, ArgumentProvider::class))
					{
						$type = (new ReflectionClass($class))->getMethod("getValue")
															 ->getReturnType();
						if($type === null)
						{
							throw new DomainException("$class's getValue() function doesn't have an explicit return type");
						}
						/** @noinspection PhpDeprecationInspection */
						$type_name = ($type instanceof ReflectionNamedType ? $type->getName() : $type->__toString());
						if(array_key_exists($type_name, self::$argument_providers))
						{
							trigger_error($class." provides {$type_name} which is already provided by ".self::$argument_providers[$type]);
						}
						else
						{
							self::$argument_providers[$type_name] = $class;
						}
					}
				}
				self::$last_declared_classes = $classes;
			}
			$this->params = array_slice($params, 1);
			foreach($this->params as $param)
			{
				$type = $param->getType();
				if($type !== null)
				{
					/** @noinspection PhpUndefinedMethodInspection */
					$type_name = ($type instanceof ReflectionNamedType ? $type->getName() : $type->__toString());
					if(!array_key_exists($type_name, self::$argument_providers))
					{
						throw new DomainException(PluginManager::$command_prefix.$this->getCanonicalName()."'s \$".$param->getName()." argument requires a value of type {$type_name} but no provider for that type is registered");
					}
				}
			}
		}
		catch(ReflectionException $e)
		{
			throw new RuntimeException("Unexpected exception: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
		}
	}

	/**
	 * @return string
	 */
	function getCanonicalName(): string
	{
		return $this->names[0];
	}

	/**
	 * Handles a message, which may be a command, in which case it will be executed.
	 *
	 * @param CommandSender $sender
	 * @param string $msg
	 * @return bool If the message was a command.
	 */
	static function handleMessage(CommandSender &$sender, string $msg): bool
	{
		$prefix_len = strlen(PluginManager::$command_prefix);
		if(substr($msg, 0, $prefix_len) == PluginManager::$command_prefix)
		{
			$args = explode(" ", $msg);
			$cmd = Command::get(strtolower(substr($args[0], $prefix_len)));
			if($cmd === null)
			{
				if(Command::get("help") === null)
				{
					$sender->sendMessage([
						"text" => "Unknown command. I would suggest using ".PluginManager::$command_prefix."help, but that's also not a known command.",
						"color" => "red"
					]);
				}
				else
				{
					$sender->sendMessage([
						"text" => "Unknown command. Use ".PluginManager::$command_prefix."help to get a list of commands.",
						"color" => "red"
					]);
				}
			}
			else
			{
				try
				{
					$cmd->call($sender, array_slice($args, 1));
				}
				catch(Exception $e)
				{
					$sender->sendMessage([
						"text" => $e->getMessage(),
						"color" => "red"
					]);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns a command using one of its names or null if not found.
	 *
	 * @param string $name
	 * @return Command|null
	 */
	static function get(string $name): ?Command
	{
		foreach(PluginManager::$registered_commands as $command)
		{
			assert($command instanceof Command);
			foreach($command->names as $cname)
			{
				if($cname == $name)
				{
					return $command;
				}
			}
		}
		return null;
	}

	/**
	 * Calls the command using the given string arguments.
	 *
	 * @param CommandSender $sender
	 * @param array<string> $args
	 * @return void
	 */
	function call(CommandSender &$sender, array $args): void
	{
		if($this->required_sender_class !== null && get_class($sender) != $this->required_sender_class && !is_subclass_of(get_class($sender), ServerCommandSender::class))
		{
			if($this->required_sender_class == ServerCommandSender::class)
			{
				$sender->sendMessage([
					"text" => "This command only for works on servers.",
					"color" => "red"
				]);
			}
			else
			{
				$sender->sendMessage([
					"text" => "This command is only for ".($this->required_sender_class == Server::class ? "the server" : "players").".",
					"color" => "red"
				]);
			}
			return;
		}
		if($this->required_permission !== null && !$sender->hasPermission($this->required_permission))
		{
			$sender->sendMessage([
				"text" => "You don't have the '{$this->required_permission}' permission required to use ".PluginManager::$command_prefix.$this->getCanonicalName().".",
				"color" => "red"
			]);
			return;
		}
		$args_ = [&$sender];
		$i = 0;
		$l = count($args);
		foreach($this->params as $param)
		{
			if($i == $l)
			{
				if(!$param->isDefaultValueAvailable())
				{
					throw new DomainException("Missing required argument ".$param->getName());
				}
				break;
			}
			$provider = self::getProvider($param->getType());
			$arg = new $provider($sender, $args[$i++]);
			assert($arg instanceof ArgumentProvider);
			while($arg->acceptsMore())
			{
				if($i == $l)
				{
					if($arg->isFinished())
					{
						break;
					}
					throw new DomainException("Argument ".$param->getName()." was not finished");
				}
				$arg->acceptNext($args[$i++]);
			}
			array_push($args_, $arg->getValue());
		}
		call_user_func_array($this->function, $args_);
	}

	/**
	 * @param ReflectionType|null $type
	 * @return string
	 */
	static function getProvider($type): string
	{
		/** @noinspection PhpDeprecationInspection */
		return $type ? (self::$argument_providers[$type instanceof ReflectionNamedType ? $type->getName() : $type->__toString()]) : StringProvider::class;
	}

	/**
	 * Returns true if the given CommandSender fulfils the class & permission requirements.
	 *
	 * @param CommandSender $sender
	 * @return bool
	 */
	function isUsableBy(CommandSender &$sender): bool
	{
		return ($this->required_sender_class === null || get_class($sender) === $this->required_sender_class || is_subclass_of(get_class($sender), $this->required_sender_class)) && ($this->required_permission === null || $sender->hasPermission($this->required_permission));
	}

	/**
	 * @return string
	 */
	function getSyntax(): string
	{
		$syntax = PluginManager::$command_prefix.$this->getCanonicalName();
		foreach($this->params as $param)
		{
			$syntax .= " ".($param->isDefaultValueAvailable() ? "[" : "<").$param->getName().($param->isDefaultValueAvailable() ? "]" : ">");
		}
		return $syntax;
	}
}

ClientConfigurationProvider::noop();
ClientConnectionProvider::noop();
FloatProvider::noop();
GreedyStringProvider::noop();
IntegerProvider::noop();
QuotedStringProvider::noop();
SingleWordStringProvider::noop();
StringProvider::noop();
