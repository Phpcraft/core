<?php
namespace Phpcraft\Realms;
use hellsh\UUID;
use Phpcraft\Account;
class Invite
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
	 * @var string $server_name
	 */
	public $server_name;
	/**
	 * @var string $server_description
	 */
	public $server_description;
	/**
	 * @var string $server_owner_name
	 */
	public $server_owner_name;
	/**
	 * @var UUID $owner_uuid
	 */
	public $server_owner_uuid;
	/**
	 * @var int $invite_time
	 */
	public $invite_time;

	function __construct(Account $account, array $data)
	{
		$this->account = $account;
		$this->id = $data["invitationId"];
		$this->server_name = $data["worldName"];
		$this->server_description = $data["worldDescription"];
		$this->server_owner_name = $data["worldOwnerName"];
		$this->server_owner_uuid = new UUID($data["worldOwnerUuid"]);
		$this->invite_time = round($data["date"] / 1000);
	}

	function accept()
	{
		$this->account->sendRealmsRequest("PUT", "/invites/accept/".$this->id);
	}

	function reject()
	{
		$this->account->sendRealmsRequest("PUT", "/invites/reject/".$this->id);
	}
}
