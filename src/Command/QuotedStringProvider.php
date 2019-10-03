<?php
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
class QuotedStringProvider extends ArgumentProvider
{
	private $has_more = false;

	public function __construct(CommandSender &$sender, string $arg)
	{
		if(substr($arg, 0, 1) != "\"")
		{
			throw new InvalidArgumentException("Quotable string has to start with \"");
		}
		$this->value = $arg;
		$this->has_more = self::hasMore($arg);
	}

	private static function hasMore(string $arg): bool
	{
		return substr($arg, -1) != "\"" || substr($arg, -2) == "\\\"";
	}

	static function write(Connection $con)
	{
		$con->writeString("brigadier:string");
		$con->writeVarInt(1); // QUOTABLE_PHRASE
	}

	function getValue(): QuotedString
	{
		return new QuotedString(substr(str_replace("\\\\", "\\", str_replace("\\\"", "\"", $this->value)), 1, -1));
	}

	function acceptNext(string $arg)
	{
		$this->value .= " ".$arg;
		$this->has_more = self::hasMore($arg);
	}

	function acceptsMore(): bool
	{
		return $this->has_more;
	}

	function isFinished(): bool
	{
		return !$this->has_more;
	}
}
