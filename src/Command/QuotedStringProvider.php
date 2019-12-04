<?php /** @noinspection PhpUnusedParameterInspection */
namespace Phpcraft\Command;
use InvalidArgumentException;
use Phpcraft\Connection;
class QuotedStringProvider extends ArgumentProvider
{
	private $has_more = false;

	function __construct(CommandSender &$sender, string $arg)
	{
		if(substr($arg, 0, 1) != "\"")
		{
			throw new InvalidArgumentException("Quotable string has to start with \"");
		}
		$this->value = $arg;
		$this->has_more = self::hasMore($arg);
	}

	/**
	 * @param string $arg
	 * @return bool
	 */
	private static function hasMore(string $arg): bool
	{
		return substr($arg, -1) != "\"" || substr($arg, -2) == "\\\"";
	}

	/**
	 * @param Connection $con
	 * @return void
	 */
	static function write(Connection $con): void
	{
		$con->writeString("brigadier:string");
		$con->writeVarInt(1); // QUOTABLE_PHRASE
	}

	/**
	 * @return QuotedString
	 */
	function getValue(): QuotedString
	{
		return new QuotedString(substr(str_replace("\\\\", "\\", str_replace("\\\"", "\"", $this->value)), 1, -1));
	}

	/**
	 * @param string $arg
	 * @return void
	 */
	function acceptNext(string $arg): void
	{
		$this->value .= " ".$arg;
		$this->has_more = self::hasMore($arg);
	}

	/**
	 * @return bool
	 */
	function acceptsMore(): bool
	{
		return $this->has_more;
	}

	/**
	 * @return bool
	 */
	function isFinished(): bool
	{
		return !$this->has_more;
	}
}
