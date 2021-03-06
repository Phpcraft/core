<?php
namespace Phpcraft\Command;
use Phpcraft\
{Connection, Exception\IOException};
/**
 * Extend this class to create a custom argument provider for Commands.
 * All declared classes will automagically be scanned and if they extends this class, it will be registered as the provider of the type that their getValue function returns.
 * User input will be given to the constructor, if it is invalid for your type, simply throw a DomainException or similar.
 * Should the type you're providing require more than one "word", feel free to override isFinished and acceptNext.
 */
abstract class ArgumentProvider
{
	protected $value;

	abstract function __construct(CommandSender &$sender, string $arg);

	/**
	 * Does nothing and shouldn't do anything.
	 * This function is called on "native" argument providers by Command so they are forced into existence.
	 *
	 * @return void
	 */
	static function noop(): void
	{
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function write(Connection $con): void
	{
		$con->writeString("brigadier:string");
		$con->writeVarInt(0); // SINGLE_WORD
	}

	/**
	 * @param Connection $con
	 * @return string
	 * @throws IOException
	 */
	static function read(Connection $con): string
	{
		switch($parser = $con->readString())
		{
			case "brigadier:double":
				$flags = $con->readByte();
				if($flags & 0x01)
				{
					$con->readDouble();
				}
				if($flags & 0x02)
				{
					$con->readDouble();
				}
				return FloatProvider::class;
			case "brigadier:float":
				$flags = $con->readByte();
				if($flags & 0x01)
				{
					$con->readFloat();
				}
				if($flags & 0x02)
				{
					$con->readFloat();
				}
				return FloatProvider::class;
			case "brigadier:integer":
				$flags = $con->readByte();
				if($flags & 0x01)
				{
					$con->readInt();
				}
				if($flags & 0x02)
				{
					$con->readInt();
				}
				return IntegerProvider::class;
			case "brigadier:string":
				switch($type = $con->readByte())
				{
					case 0:
						return SingleWordStringProvider::class;
					case 1:
						return QuotedStringProvider::class;
					case 2:
						return GreedyStringProvider::class;
				}
				throw new IOException("Invalid string type: $type");
			case "minecraft:entity":
			case "minecraft:score_holder":
			case "minecraft:range":
				$con->ignoreBytes(1);
		}
		trigger_error("Unimplemented parser: $parser");
		return UnimplementedParser::class;
	}

	/**
	 * @return mixed
	 */
	abstract function getValue();

	/**
	 * @return bool
	 */
	function acceptsMore(): bool
	{
		return false;
	}

	/**
	 * @return bool
	 */
	function isFinished(): bool
	{
		return true;
	}

	/**
	 * @param string $arg
	 * @return void
	 */
	function acceptNext(string $arg): void
	{
	}
}
