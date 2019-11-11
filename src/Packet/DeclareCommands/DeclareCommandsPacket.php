<?php
namespace Phpcraft\Packet\DeclareCommands;
use Phpcraft\
{Command\Command, Connection, Exception\IOException, Packet\Packet};
class DeclareCommandsPacket extends Packet
{
	/**
	 * @var RootNode $root_node
	 */
	public $root_node;

	function __construct(RootNode $root_node = null)
	{
		$this->root_node = $root_node ?? new RootNode();
	}

	/**
	 * Initialises the packet class by reading its payload from the given Connection.
	 *
	 * @param Connection $con
	 * @return DeclareCommandsPacket
	 * @throws IOException
	 */
	static function read(Connection $con): DeclareCommandsPacket
	{
		$stack = [];
		$stack_size = $con->readVarInt();
		for($i = 0; $i < $stack_size; $i++)
		{
			array_push($stack, Node::read($con));
		}
		foreach($stack as $node)
		{
			$children = [];
			foreach($node->children as $child)
			{
				array_push($children, $stack[$child]);
			}
			$node->children = $children;
			if($node->redirect_to)
			{
				$node->redirect_to = $stack[$node->redirect_to];
			}
		}
		$root_node = $stack[gmp_intval($con->readVarInt())];
		if(!$root_node instanceof RootNode)
		{
			throw new IOException("Root node index points to non-root node");
		}
		return new DeclareCommandsPacket($root_node);
	}

	/**
	 * @param Command $command
	 * @param string $prefix
	 * @return DeclareCommandsPacket
	 */
	function addCommand(Command &$command, string $prefix = ""): DeclareCommandsPacket
	{
		$lit = new LiteralNode($prefix.$command->names[0]);
		$_arg = null;
		$arg = null;
		foreach($command->params as $param)
		{
			if($param->isDefaultValueAvailable())
			{
				if($arg instanceof ArgumentNode)
				{
					$arg->executable = true;
				}
				else
				{
					$lit->executable = true;
				}
			}
			if($arg instanceof ArgumentNode)
			{
				if($_arg instanceof ArgumentNode)
				{
					array_push($_arg->children, $arg);
				}
				else
				{
					array_push($lit->children, $arg);
				}
				$_arg = $arg;
			}
			$arg = new ArgumentNode($param->getName(), Command::getProvider($param->getType()));
		}
		if($arg instanceof ArgumentNode)
		{
			$arg->executable = true;
			if($_arg instanceof ArgumentNode)
			{
				array_push($_arg->children, $arg);
			}
			else
			{
				array_push($lit->children, $arg);
			}
		}
		array_push($this->root_node->children, $lit);
		for($i = 1; $i < count($command->names); $i++)
		{
			array_push($this->root_node->children, new LiteralNode($prefix.$command->names[$i], $lit));
		}
		return $this;
	}

	/**
	 * Adds the packet's ID and payload to the Connection's write buffer and sends it over the wire if the connection has a stream.
	 * Note that in some cases this will produce multiple Minecraft packets, therefore you should only use this on connections without a stream if you know what you're doing.
	 *
	 * @param Connection $con
	 * @return void
	 * @throws IOException
	 */
	function send(Connection $con): void
	{
		if($con->protocol_version >= 393)
		{
			$stack = self::recursivelyFlatten($this->root_node);
			$con->startPacket("declare_commands");
			$con->writeVarInt(count($stack));
			foreach($stack as $node)
			{
				$node->write($con, $stack);
			}
			$con->writeVarInt(0);
			$con->send();
		}
	}

	/**
	 * @param Node $node
	 * @return Node[]
	 */
	static function recursivelyFlatten(Node &$node): array
	{
		$flattened = [$node];
		foreach($node->children as $child)
		{
			$flattened = array_merge($flattened, self::recursivelyFlatten($child));
		}
		return $flattened;
	}

	function __toString()
	{
		return "{DeclareCommandsPacket: {$this->root_node}}";
	}
}
