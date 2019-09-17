<?php
namespace Phpcraft\Packet\DeclareCommands;
use Phpcraft\
{Command\ArgumentProvider, Connection, Exception\IOException};
use RuntimeException;
abstract class Node
{
	const TYPE_ID = 3;
	/**
	 * @var array $children
	 */
	public $children = [];
	/**
	 * @var bool $executable
	 */
	public $executable = false;
	/**
	 * @var Node|null $redirect_to
	 */
	public $redirect_to;

	/**
	 * @param Connection $con
	 * @return Node
	 * @throws IOException
	 */
	static function read(Connection $con): Node
	{
		$flags = $con->readByte();
		if($has_suggestions_type = ($flags >= 0x10))
		{
			$flags -= 0x10;
		}
		if($has_redirect = ($flags >= 0x08))
		{
			$flags -= 0x08;
		}
		if($executable = ($flags >= 0x04))
		{
			$flags -= 0x04;
		}
		switch($flags)
		{
			case 0:
				$node = new RootNode();
				break;
			case 1:
				$node = new LiteralNode();
				break;
			case 2:
				$node = new ArgumentNode();
				break;
			default:
				throw new IOException("Invalid node type: $flags");
		}
		$children = gmp_intval($con->readVarInt());
		for($i = 0; $i < $children; $i++)
		{
			array_push($node->children, gmp_intval($con->readVarInt()));
		}
		if($has_redirect)
		{
			$node->redirect_to = gmp_intval($con->readVarInt());
		}
		if(!$node instanceof RootNode)
		{
			$node->name = $con->readString();
		}
		if($node instanceof ArgumentNode)
		{
			$node->provider = ArgumentProvider::read($con);
		}
		if($has_suggestions_type)
		{
			if(!$node instanceof ArgumentNode)
			{
				throw new IOException("Non-argument node can't have a suggestions tpye");
			}
			$con->readString();
		}
		$node->executable = $executable;
		return $node;
	}

	function write(Connection $con, array &$stack): Connection
	{
		$flags = static::TYPE_ID;
		if($this->executable)
		{
			$flags |= 0x04;
		}
		if($this->redirect_to)
		{
			$redirect_index = array_search($this->redirect_to, $stack, true);
			if($redirect_index === false)
			{
				throw new RuntimeException("Can't write $this because the target node is not in the stack");
			}
			$flags |= 0x08;
		}
		$con->writeByte($flags);
		$con->writeVarInt(count($this->children));
		foreach($this->children as $child)
		{
			$index = array_search($child, $stack, true);
			if($index === false)
			{
				throw new RuntimeException("Can't write $this because child $child is not in the stack");
			}
			$con->writeVarInt($index);
		}
		if(isset($redirect_index))
		{
			$con->writeVarInt($redirect_index);
		}
		if(!$this instanceof RootNode)
		{
			$con->writeString($this->name);
		}
		if($this instanceof ArgumentNode)
		{
			call_user_func($this->provider."::write", $con);
		}
		return $con;
	}

	abstract function __toString();
}
