<?php
namespace Phpcraft\Command;
use DomainException;
use Phpcraft\
{Plugin\Plugin, Plugin\PluginManager};
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
class Command
{
	static private $argument_providers;
	/**
	 * @var $last_declared_classes array
	 */
	static private $last_declared_classes = [];
	/**
	 * @var $plugin Plugin
	 */
	public $plugin;
	/**
	 * @var $names string[]
	 */
	public $names;
	/**
	 * @var $functon callable
	 */
	private $function;
	/**
	 * @var $params ReflectionParameter[]
	 */
	private $params;

	function __construct(Plugin $plugin, array $names, callable $function)
	{
		$this->plugin = $plugin;
		$this->names = $names;
		$this->function = $function;
		try
		{
			$params = (new ReflectionFunction($this->function))->getParameters();
			if(count($params) > 0)
			{
				$type = $params[0]->getType();
				/** @noinspection PhpDeprecationInspection */
				if($type !== null && ($type->isBuiltin() || ($type instanceof ReflectionNamedType ? $type->getName() : $type->__toString()) != CommandSender::class))
				{
					throw new DomainException("/".$this->getCanonicalName()."'s first parameter's type should be ".CommandSender::class." or not restricted at all");
				}
			}
			$classes = get_declared_classes();
			if($new_classes = array_diff($classes, self::$last_declared_classes))
			{
				if(self::$argument_providers === null)
				{
					self::$argument_providers = [
						null => StringArgumentProvider::class
					];
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
							/** @noinspection PhpDeprecationInspection */
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
					/** @noinspection PhpDeprecationInspection */
					$type_name = ($type instanceof ReflectionNamedType ? $type->getName() : $type->__toString());
					if(!array_key_exists($type_name, self::$argument_providers))
					{
						throw new DomainException($this->getCanonicalName()."'s ".$param->getName()." argument requires a value of type {$type_name} but no provider for that type is registered");
					}
				}
			}
		}
		catch(ReflectionException $e)
		{
			throw new RuntimeException("Unexpected exception: ".get_class($e).": ".$e->getMessage()."\n".$e->getTraceAsString());
		}
	}

	function getCanonicalName(): string
	{
		return $this->names[0];
	}

	/**
	 * Returns a command using one of its names or null if not found.
	 *
	 * @param string $name
	 * @return Command|null
	 */
	static function get(string $name)
	{
		foreach(PluginManager::$registered_commands as $command)
		{
			/**
			 * @var $command Command
			 */
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

	function getSyntax()
	{
		$syntax = "/".$this->getCanonicalName();
		foreach($this->params as $param)
		{
			$syntax .= " ".($param->isDefaultValueAvailable() ? "[" : "<").$param->getName().($param->isDefaultValueAvailable() ? "]" : ">");
		}
		return $syntax;
	}

	/**
	 * Calls the command using the given string arguments.
	 *
	 * @param CommandSender $sender
	 * @param $args string[]
	 */
	function call(CommandSender &$sender, array $args)
	{
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
			/** @noinspection PhpDeprecationInspection */
			$provider = self::$argument_providers[$param->getType() instanceof ReflectionNamedType ? $param->getType()
																										   ->getName() : $param->getType()
																															   ->__toString()];
			$arg = new $provider($args[$i++]);
			/**
			 * @var $arg ArgumentProvider
			 */
			while(!$arg->isFinished())
			{
				if($i == $l)
				{
					throw new DomainException("Argument ".$param->getName()." was not finished");
				}
				$arg->acceptNext($args[$i++]);
			}
			array_push($args_, $arg->getValue());
		}
		call_user_func_array($this->function, $args_);
	}
}

StringArgumentProvider::noop();
IntegerArgumentProvider::noop();
FloatArgumentProvider::noop();
