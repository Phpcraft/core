<?php
namespace Phpcraft;
require_once __DIR__."/Connection.class.php";
/** A client-to-server connection. */
class ServerConnection extends Connection
{
	private $username;
	private $uuid;

	/**
	 * The constructor.
	 * @param resource $stream A stream created by fsockopen.
	 * @param integer $protocol_version 404 = 1.13.2
	 */
	function __construct($stream, $protocol_version = 404)
	{
		parent::__construct($protocol_version, $stream);
	}

	/**
	 * Sends a handshake to the server.
	 * If $next_state is 2, you should call ServerConnection::login() after this, even when joining an offline mode server.
	 * @param string $server_name
	 * @param integer $server_port
	 * @param integer $next_state Use 1 for status, or 2 for login to play.
	 * @return ServerConnection $this
	 */
	function sendHandshake($server_name, $server_port, $next_state)
	{
		$this->writeVarInt(0x00);
		$this->writeVarInt($this->protocol_version);
		$this->writeString($server_name);
		$this->writeShort($server_port);
		$this->writeVarInt($this->state = $next_state);
		$this->send();
		return $this;
	}

	/**
	 * Returns our name on the server.
	 * The return value will be equal to the return value of Account::getUsername() of the account passed to ServerConnection::login().
	 * @return string
	 */
	function getUsername()
	{
		return $this->username;
	}

	/**
	 * Returns our UUID on the server with hypens.
	 * @return string
	 */
	function getUUID()
	{
		return $this->uuid;
	}

	/**
	 * Logs in to the server using the given account.
	 * This has to be called even when joining an offline mode server.
	 * @param Account $account
	 * @param array $translations The translations array so translated messages look proper.
	 * @return string Error message. Empty on success.
	 */
	function login($account, $translations = null)
	{
		$this->writeVarInt(0x00);
		$this->writeString($account->getUsername());
		$this->send();
		do
		{
			$id = $this->readPacket();
			if($id == 0x04) // Login Plugin Request
			{
				$this->writeVarInt(0x02); // Login Plugin Response
				$this->writeVarInt($this->readVarInt());
				$this->writeBoolean(false);
				$this->send();
			}
			else if($id == 0x03) // Set Compression
			{
				$this->compression_threshold = $this->readVarInt();
			}
			else if($id == 0x02) // Login Success
			{
				$this->uuid = $this->readString(36);
				$name = $this->readString(16);
				if($account->getUsername() != $name)
				{
					return "Server did not accept our username and would rather call us '{$name}'.";
				}
				$this->username = $name;
				$this->state = 3;
				return "";
			}
			else if($id == 0x01) // Encryption Request
			{
				if(!$account->isOnline())
				{
					return "The server is in online mode.";
				}
				$server_id = $this->readString(20);
				$public_key = $this->readString();
				$verify_token = $this->readString();
				$shared_secret = "";
				for($i = 0; $i < 16; $i++)
				{
					$shared_secret .= chr(rand(0, 255));
				}
				if(Phpcraft::httpPOST("https://sessionserver.mojang.com/session/minecraft/join", [
					"accessToken" => $account->getAccessToken(),
					"selectedProfile" => $account->getProfileId(),
					"serverId" => Phpcraft::sha1($server_id.$shared_secret.$public_key)
				]) === false)
				{
					return "The session server is down for maintenance.";
				}
				$public_key = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".base64_encode($public_key)."\n-----END PUBLIC KEY-----");
				$this->writeVarInt(0x01); // Encryption Response
				$crypted = "";
				openssl_public_encrypt($shared_secret, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				openssl_public_encrypt($verify_token, $crypted, $public_key, OPENSSL_PKCS1_PADDING);
				$this->writeString($crypted);
				$this->send();
				$opts = ["mode" => "cfb", "iv" => $shared_secret, "key" => $shared_secret];
				stream_filter_append($this->stream, "mcrypt.rijndael-128", STREAM_FILTER_WRITE, $opts);
				stream_filter_append($this->stream, "mdecrypt.rijndael-128", STREAM_FILTER_READ, $opts);
			}
			else if($id == 0x00) // Disconnect
			{
				return Phpcraft::chatToText(json_decode($this->readString()));
			}
			else
			{
				return "Unexpected response: {$id} ".bin2hex($this->read_buffer);
			}
		}
		while(true);
	}
}
