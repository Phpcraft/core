<?php
namespace Phpcraft;
class AssetsManager
{
	public $version;

	/**
	 * The constructor.
	 * @param string $version The Minecraft version you'd like to access the assets of.
	 */
	function __construct($version)
	{
		$this->version = strtolower($version);
	}

	/**
	 * Returns the JSON-decoded content of the asset index of the version.
	 * @return array
	 */
	function getAssetIndex()
	{
		$versions_folder = \Phpcraft\Phpcraft::getMinecraftFolder()."/versions";
		if(!file_exists($versions_folder) || !is_dir($versions_folder))
		{
			mkdir($versions_folder);
		}
		$version_folder = $versions_folder."/".$this->version;
		if(!file_exists($version_folder) || !is_dir($version_folder))
		{
			mkdir($version_folder);
		}
		$version_manifest = $version_folder."/".$this->version.".json";
		if(!file_exists($version_manifest) || !is_file($version_manifest))
		{
			foreach(json_decode(Phpcraft::getCachableResource("https://launchermeta.mojang.com/mc/game/version_manifest.json"), true)["versions"] as $version)
			{
				if($version["id"] == $this->version)
				{
					file_put_contents($version_manifest, file_get_contents($version["url"]));
					break;
				}
			}
			if(!file_exists($version_manifest) || !is_file($version_manifest))
			{
				throw new \Phpcraft\Exception("Failed to get version manifest for ".$this->version);
			}
		}
		$assets_dir = \Phpcraft\Phpcraft::getMinecraftFolder()."/assets";
		if(!file_exists($assets_dir) || !is_dir($assets_dir))
		{
			mkdir($assets_dir);
		}
		$asset_index_dir = $assets_dir."/indexes";
		if(!file_exists($asset_index_dir) || !is_dir($asset_index_dir))
		{
			mkdir($asset_index_dir);
		}
		$json = json_decode(file_get_contents($version_manifest), true);
		$asset_index = $asset_index_dir."/".$json["assets"];
		if(!file_exists($asset_index) || !is_file($asset_index))
		{
			file_put_contents($asset_index, file_get_contents($json["assetIndex"]["url"]));
		}
		return json_decode(file_get_contents($asset_index), true);
	}

	/**
	 * Checks the asset index for the existence of an asset.
	 * @param string $name
	 * @return boolean
	 */
	function doesAssetExist($name)
	{
		return isset($this->getAssetIndex()["objects"][$name]);
	}

	/**
	 * Downloads an asset by name and returns the path to the downloaded file or null if the asset doesn't exist.
	 * @param string $name
	 * @return string
	 */
	function downloadAsset($name)
	{
		$asset_index = $this->getAssetIndex();
		$objects_dir = \Phpcraft\Phpcraft::getMinecraftFolder()."/assets/objects";
		if(!file_exists($objects_dir) || !is_dir($objects_dir))
		{
			mkdir($objects_dir);
		}
		if($asset = $asset_index["objects"][$name])
		{
			$hash = $asset_index["objects"][$name]["hash"];
			$dir = $objects_dir."/".substr($hash, 0, 2);
			if(!file_exists($dir) || !is_dir($dir))
			{
				mkdir($dir);
			}
			$file = $dir."/".$hash;
			if(!file_exists($file) || !is_file($file))
			{
				file_put_contents($file, file_get_contents("https://resources.download.minecraft.net/".substr($hash, 0, 2)."/".$hash));
			}
			return $file;
		}
		return null;
	}

	/**
	 * Downloads all assets.
	 * @return void
	 */
	function downloadAllAssets()
	{
		foreach($this->getAssetIndex()["objects"] as $name => $object)
		{
			$this->downloadAsset($name);
		}
	}

	/**
	 * Builds the legacy assets folder for versions before 1.7.2.
	 * @return void
	 */
	function buildLegacyAssetsFolder()
	{
		$asset_index = $this->getAssetIndex();
		$virtual_dir = \Phpcraft\Phpcraft::getMinecraftFolder()."/assets/virtual";
		\Phpcraft\Phpcraft::recursivelyDelete($virtual_dir);
		mkdir($virtual_dir);
		$legacy_dir = $virtual_dir."/legacy";
		mkdir($legacy_dir);
		file_put_contents($legacy_dir."/READ_ME_I_AM_VERY_IMPORTANT.txt", " _    _  ___  ______ _   _ _____ _   _ _____ \n| |  | |/ _ \\ | ___ \\ \\ | |_   _| \\ | |  __ \\\n| |  | / /_\\ \\| |_/ /  \\| | | | |  \\| | |  \\/\n| |/\\| |  _  ||    /| . ` | | | | . ` | | __ \n\\  /\\  / | | || |\\ \\| |\\  |_| |_| |\\  | |_\\ \\\n \\/  \\/\\_| |_/\\_| \\_\\_| \\_/\\___/\\_| \\_/\\____/\n\n(Sorry about the cheesy 90s ASCII art.)\n\nEverything in this folder that does not belong here will be deleted.\nThis folder will be kept sync with the Launcher at every run.\nIf you wish to modify assets/resources in any way, use Resource Packs.\n\n\nTa,\nDinnerbone of Mojang");
		foreach($asset_index["objects"] as $name => $object)
		{
			$path = $this->downloadAsset($name);
			if(substr($name, 0, 10) == "minecraft/")
			{
				$name = substr($name, 10);
			}
			$legacy_file = $legacy_dir."/".$name;
			if(!file_exists($legacy_file) || !is_file($legacy_file))
			{
				$arr = explode("/", $legacy_file);
				unset($arr[count($arr) - 1]);
				$parent = join("/", $arr);
				if(!file_exists($parent) || !is_dir($parent))
				{
					mkdir(join("/", $arr), 0777, true);
				}
				copy($path, $legacy_file);
			}
		}
	}
}