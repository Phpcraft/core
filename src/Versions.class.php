<?php
namespace Phpcraft;
abstract class Versions
{
	/**
	 * Returns an associative array of supported Minecraft versions with their protocol version as value; newest first.
	 * @return array
	 * @see Versions::releases
	 */
	public static function all()
	{
		return [
			"1.14.2" => 485,
			"1.14.2-pre4" => 484,
			"1.14.2-pre3" => 483,
			"1.14.2-pre2" => 482,
			"1.14.2-pre1" => 481,
			"1.14.1" => 480,
			"1.14.1-pre2" => 479,
			"1.14.1-pre1" => 478,
			"1.14" => 477,
			"1.14-pre5" => 476,
			"1.14-pre4" => 475,
			"1.14-pre3" => 474,
			"1.14-pre2" => 473,
			"1.14-pre1" => 472,
			"1.13.2" => 404,
			"1.13.2-pre2" => 403,
			"1.13.2-pre1" => 402,
			"1.13.1" => 401,
			"1.13.1-pre2" => 400,
			"1.13.1-pre1" => 399,
			"18w33a" => 398,
			"18w32a" => 397,
			"18w31a" => 395,
			"18w30a" => 394,
			"1.13" => 393,
			"1.12.2" => 340,
			"1.12.2-pre2" => 339,
			"1.12.1" => 338,
			"1.12.1-pre2" => 337,
			"1.12.1-pre1" => 337,
			"17w31a" => 336,
			"1.12" => 335,
			"1.11.2" => 316,
			"1.11.1" => 316,
			"1.11" => 315,
			"1.11-pre1" => 314,
			"16w44a" => 313,
			"16w43a" => 313,
			"16w42a" => 312,
			"16w41a" => 311,
			"16w40a" => 310,
			"1.10.2" => 210,
			"1.10.1" => 210,
			"1.10" => 210,
			"1.9.4" => 110,
			"1.9.3" => 110,
			"1.9.2" => 109,
			"1.9.1" => 108,
			"1.9" => 107,
			"1.9-pre4" => 106,
			"1.9-pre3" => 105,
			"1.9-pre2" => 104,
			"1.9-pre1" => 103,
			"16w07b" => 102,
			"16w07a" => 101,
			"1.8.9" => 47,
			"1.8.8" => 47,
			"1.8.7" => 47,
			"1.8.6" => 47,
			"1.8.5" => 47,
			"1.8.4" => 47,
			"1.8.3" => 47,
			"1.8.2" => 47,
			"1.8.1" => 47,
			"1.8" => 47
		];
	}

	/**
	 * Returns an associative array of supported non-snapshot Minecraft versions with their protocol version as value; newest first.
	 * @return array
	 * @see Versions::all
	 */
	public static function releases()
	{
		$releases = [];
		foreach(Versions::all() as $id => $pv)
		{
			if(preg_match('/^1\.[0-9]+(\.[0-9])?$/', $id))
			{
				$releases[$id] = $pv;
			}
		}
		return $releases;
	}

	/**
	 * Returns a list of supported protocol versions; newest first.
	 * @return integer[]
	 */
	public static function protocol()
	{
		return array_values(Versions::all());
	}

	/**
	 * Returns whether a given protocol version is supported.
	 * @param integer $protocol_version e.g., 340
	 * @return boolean
	 */
	public static function protocolSupported(int $protocol_version)
	{
		return in_array($protocol_version, Versions::all());
	}

	/**
	 * Returns the protocol version corresponding to the given Minecraft version; newest first.
	 * @param string $minecraft_version
	 * @return integer The protocol version or null if the Minecraft version is not supported.
	 */
	public static function minecraftToProtocol(string $minecraft_version)
	{
		return @Versions::all()[$minecraft_version];
	}

	/**
	 * Returns an array of all supported Minecraft versions; newest first.
	 * @return string[]
	 */
	public static function minecraft()
	{
		return array_keys(Versions::all());
	}

	/**
	 * Returns an array of all supported Minecraft release versions; newest first.
	 * @return string[]
	 */
	public static function minecraftReleases()
	{
		return array_keys(Versions::releases());
	}

	/**
	 * Returns whether a given Minecraft version is supported.
	 * @param string $minecraft_version
	 * @return boolean
	 */
	public static function minecraftSupported(string $minecraft_version)
	{
		return isset(Versions::all()[$minecraft_version]);
	}

	/**
	 * Returns an array of Minecraft versions corresponding to the given protocol version; newest first.
	 * @param integer $protocol_version
	 * @return string[]
	 */
	public static function protocolToMinecraft(int $protocol_version)
	{
		$minecraft_versions = [];
		foreach(Versions::all() as $k => $v)
		{
			if($v == $protocol_version)
			{
				array_push($minecraft_versions, $k);
			}
		}
		return $minecraft_versions;
	}

	/**
	 * Returns a human-readable range of Minecraft versions corresponding to the given protocol version, e.g. 47 would return "1.8 - 1.8.9"
	 * @param integer $protocol_version
	 * @return string The version range or an empty string if the given protocol version is not supported.
	 */
	public static function protocolToRange(int $protocol_version)
	{
		$minecraft_versions = Versions::protocolToMinecraft($protocol_version);
		$count = count($minecraft_versions);
		if($count > 0)
		{
			if($count == 1)
			{
				return $minecraft_versions[0];
			}
			return $minecraft_versions[$count - 1]." - ".$minecraft_versions[0];
		}
		return "";
	}
}
