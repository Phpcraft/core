<?php /** @noinspection PhpUnusedParameterInspection */
/**
 * This plugin provides all the standard dot commands in the Phpcraft client.
 *
 * @var Plugin $this
 */
use Phpcraft\
{Plugin, PluginManager, ServerConnection};
if(PluginManager::$command_prefix != ".")
{
	$this->unregister();
	return;
}
$this->registerCommand([
	"help",
	"?"
], function(ServerConnection &$con)
{
	echo "Yay! You've found commands, which start with a period.\n";
	echo "If you want to send a message starting with a period, use two periods.\n";
	echo "?, help                    shows this help\n";
	echo "pos                        returns the current position\n";
	echo "move <y>, move <x> [y] <z> initates movement\n";
	echo "rotate, rot <yaw> <pitch>  change yaw and pitch\n";
	echo "list                       lists all players in the player list\n";
	echo "entities                   lists all player entities\n";
	echo "follow <name>              follows <name>'s player entity\n";
	echo "unfollow                   stops following whoever is being followed\n";
	echo "slot <1-9>                 sets selected hotbar slot\n";
	echo "hit                        swings the main hand\n";
	echo "use                        uses the held item\n";
	echo "reload                     reloads all plugins\n";
	echo "reconnect                  reconnects to the server\n";
	echo "quit, disconnect           disconnects from the server\n";
});
$this->registerCommand("pos", function(ServerConnection &$con)
{
	global $x, $y, $z;
	echo "$x $y $z\n";
});
$this->registerCommand("move", function(ServerConnection &$con, float $x_or_y = null, float $y_or_z = null, float $z = null)
{
	global $followEntity;
	if($followEntity !== false)
	{
		echo "I'm currently following {$followEntity}.\n";
	}
	else
	{
		global $motion_x, $motion_y, $motion_z;
		if($z !== null)
		{
			$motion_x += $x_or_y;
			$motion_y += $y_or_z;
			$motion_z += $z;
			echo "Understood.\n";
		}
		else if($y_or_z !== null)
		{
			$motion_x += $x_or_y;
			$motion_z += $y_or_z;
			echo "Understood.\n";
		}
		else if($x_or_y !== null)
		{
			$motion_y += $x_or_y;
			echo "Understood.\n";
		}
		else
		{
			echo "Syntax: .move <y>, .move <x> [y] <z>\n";
		}
	}
});
$this->registerCommand([
	"rotate",
	"rot"
], function(ServerConnection &$con, float $new_yaw, float $new_pitch)
{
	global $followEntity;
	if($followEntity !== false)
	{
		echo "I'm currently following someone.\n";
	}
	else
	{
		global $yaw, $pitch;
		$yaw = $new_yaw;
		$pitch = $new_pitch;
		echo "Understood.\n";
	}
});
$this->registerCommand("hit", function(ServerConnection &$con)
{
	$con->startPacket("swing_arm");
	if($con->protocol_version > 47)
	{
		$con->writeVarInt(0);
	}
	$con->send();
	echo "Done.\n";
});
$this->registerCommand("use", function(ServerConnection &$con)
{
	if($con->protocol_version > 47)
	{
		$con->startPacket("use_item");
		$con->writeVarInt(0);
	}
	else
	{
		$con->startPacket("player_block_placement");
		$con->writePosition($con->pos);
		$con->writeByte(-1); // Face
		$con->writeShort(-1); // Slot
		$con->writeByte(-1); // Cursor X
		$con->writeByte(-1); // Cursor Y
		$con->writeByte(-1); // Cursor Z
	}
	$con->send();
	echo "Done.\n";
});
$this->registerCommand("list", function(ServerConnection &$con)
{
	$gamemodes = [
		0 => "Survival",
		1 => "Creative",
		2 => "Adventure",
		3 => "Spectator"
	];
	global $players;
	foreach($players as $uuid => $player)
	{
		echo $uuid."  ".$player["name"].str_repeat(" ", 17 - strlen($player["name"])).str_repeat(" ", 5 - strlen($player["ping"])).$player["ping"]." ms  ".(array_key_exists($player["gamemode"], $gamemodes) ? $gamemodes[$player["gamemode"]]." Mode" : "Gamemode ".$player["gamemode"])."\n";
	}
});
$this->registerCommand("entities", function(ServerConnection &$con)
{
	global $entities;
	foreach($entities as $eid => $entity)
	{
		echo $entity["uuid"]." as ".$eid." at ".$entity["x"]." ".$entity["y"]." ".$entity["z"]."\n";
	}
});
$this->registerCommand("follow", function(ServerConnection &$con, string $name)
{
	$username = null;
	$uuids = [];
	global $players;
	foreach($players as $uuid => $player)
	{
		if(stristr($player["name"], $name))
		{
			$uuids[$player["name"]] = $uuid;
			$username = $player["name"];
		}
	}
	if($username == null)
	{
		echo "Couldn't find {$name}.\n";
	}
	else if(count($uuids) > 1)
	{
		echo "Ambiguous name; found: ".join(", ", array_keys($uuids))."\n";
	}
	else
	{
		global $followEntity, $entities;
		$followEntity = false;
		$uuid = $uuids[$username];
		foreach($entities as $eid => $entity)
		{
			if($entity["uuid"] == $uuid)
			{
				$followEntity = $eid;
				break;
			}
		}
		if($followEntity === false)
		{
			echo "Couldn't find {$username}'s entity.\n";
		}
		else
		{
			echo "Understood.\n";
		}
	}
});
$this->registerCommand("unfollow", function(ServerConnection &$con)
{
	global $followEntity;
	$followEntity = false;
	echo "Done.\n";
});
$this->registerCommand("slot", function(ServerConnection &$con, int $slot)
{
	$con->startPacket("held_item_change");
	$con->writeShort($slot - 1);
	$con->send();
	echo "Done.\n";
});
$this->registerCommand("reload", function(ServerConnection &$con)
{
	loadPlugins();
});
$this->registerCommand("reconnect", function(ServerConnection &$con)
{
	global $reconnect;
	$reconnect = true;
});
$this->registerCommand([
	"quit",
	"disconnect"
], function(ServerConnection &$con)
{
	global $options;
	$options["noreconnect"] = true;
	$con->close();
});
