<?php
namespace Phpcraft\Realms;
use hellsh\UUID;
use Phpcraft\Account;
class Server
{
	/**
	 * @var Account $account
	 */
	public $account;
	/**
	 * @var int $id
	 */
	public $id;
	/**
	 * @var string $name
	 */
	public $name;
	/**
	 * @var string $description
	 */
	public $description;
	/**
	 * @var string $owner_name
	 */
	public $owner_name;
	/**
	 * @var UUID $owner_uuid
	 */
	public $owner_uuid;
	/**
	 * The state of the realm. Can be ADMIN_LOCK, CLOSED, OPEN, or UNINITIALIZED.
	 *
	 * @var string $state
	 */
	public $state;
	/**
	 * @var int $days_left
	 */
	public $days_left;
	/**
	 * @var bool $expired
	 */
	public $expired;
	/**
	 * @var bool $expired_trial
	 */
	public $expired_trial;
	/**
	 * The type of the world. Can be NORMAL, MINIGAME, ADVENTUREMAP, EXPERIENCE, or INSPIRATION.
	 *
	 * @var string $world_type
	 */
	public $world_type;
	/**
	 * @var $players string[]
	 */
	public $players;
	/**
	 * @var int $max_players
	 */
	public $max_players;
	/**
	 * The name of the minigame that is currently active or null if not applicable.
	 *
	 * @var string|null $minigame_name
	 */
	public $minigame_name;
	/**
	 * @var int|null $minigame_id
	 */
	public $minigame_id;
	/**
	 * The base64-encoded image for the minigame that is currently active or null if not applicable.
	 *
	 * @var string|null $minigame_image
	 */
	public $minigame_image;

	function __construct(Account $account, array $data)
	{
		$this->account = $account;
		$this->id = $data["id"];
		$this->owner_name = $data["owner"];
		$this->owner_uuid = new UUID($data["ownerUUID"]);
		$this->name = $data["name"];
		$this->description = $data["motd"];
		$this->state = $data["state"];
		$this->days_left = $data["daysLeft"];
		$this->expired = $data["expired"];
		$this->expired_trial = $data["expiredTrial"];
		$this->world_type = $data["worldType"];
		$this->players = $data["players"];
		$this->max_players = $data["maxPlayers"];
		$this->minigame_name = $data["minigameName"];
		$this->minigame_id = $data["minigameId"];
		$this->minigame_image = $data["minigameImage"];
	}

	/**
	 * Returns information required to join a realms server.
	 *
	 * @param bool $await_start
	 * @return array|null An array containing "address" (in [host]:[port] format), "resourcePackUrl" (string|null), and "resourcePackHash" (string|null). If $await_start is false and the realms server is not online, null is returned.
	 */
	function join(bool $await_start = true)
	{
		$res = json_decode($this->account->sendRealmsRequest("GET", "/worlds/v1/{$this->id}/join/pc"), true);
		if($res === null) // Retry again later
		{
			if(!$await_start)
			{
				return null;
			}
			sleep(3);
			return $this->join();
		}
		if(isset($res["errorCode"]) && $res["errorCode"] == 6002) // ToS not accepted
		{
			$this->account->sendRealmsRequest("POST", "/mco/tos/agreed");
			return $this->join();
		}
		return $res;
	}
}
