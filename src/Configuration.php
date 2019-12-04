<?php
namespace Phpcraft;
use ArrayAccess;
use Countable;
use Iterator;
use pas\pas;
use SplObjectStorage;
class Configuration implements Iterator, Countable, ArrayAccess
{
	/**
	 * @var SplObjectStorage $save_queue
	 */
	static $save_queue;
	private static $registered_loop = false;
	public $file;
	public $data;
	public $unsaved_changes = false;
	private $current = 0;

	function __construct($file = null, $default_data = null)
	{
		if($file !== null)
		{
			$this->file = $file;
			$dir = dirname($file);
			if(!is_dir($dir))
			{
				mkdir($dir);
			}
			if(is_file($file))
			{
				$this->data = json_decode(file_get_contents($file), true);
			}
			else if($default_data !== null)
			{
				$this->data = $default_data;
				$this->queueSave();
				$this->unsaved_changes = false;
			}
			else
			{
				$this->data = [];
			}
		}
		if(!self::$registered_loop)
		{
			pas::addInessential(function()
			{
				Configuration::handleQueue(0.05);
			}, 0.1);
			self::$registered_loop = true;
		}
	}

	/**
	 * Queues the configuration for saving.
	 *
	 * @return Configuration $this
	 */
	function queueSave(): Configuration
	{
		$this->unsaved_changes = true;
		if($this->file !== null)
		{
			self::$save_queue->attach($this);
		}
		return $this;
	}

	/**
	 * @param float $time_limit
	 * @return void
	 */
	static function handleQueue(float $time_limit = 0.0): void
	{
		$start = microtime(true);
		foreach(Configuration::$save_queue as $config)
		{
			$config->save();
			if($time_limit > 0 && microtime(true) - $start > $time_limit)
			{
				break;
			}
		}
	}

	function __destruct()
	{
		if($this->unsaved_changes)
		{
			$this->save();
		}
	}

	/**
	 * Forces a save, removing the configuration from the save queue.
	 *
	 * @return Configuration $this
	 */
	function save(): Configuration
	{
		if($this->file !== null)
		{
			file_put_contents($this->file, json_encode($this->data, JSON_UNESCAPED_SLASHES));
			$this->unsaved_changes = false;
		}
		self::$save_queue->detach($this);
		return $this;
	}

	function get(string $key, $default_value = null)
	{
		return @$this->data[$key] ?? $default_value;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	function has(string $key): bool
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * @param string $file
	 * @return Configuration
	 */
	function setFile(string $file): Configuration
	{
		$this->file = $file;
		$dir = dirname($file);
		if(is_dir($dir))
		{
			if(is_file($file))
			{
				$this->data = json_decode(file_get_contents($file), true);
				$this->unsaved_changes = false;
			}
		}
		else
		{
			mkdir($dir);
		}
		if($this->unsaved_changes)
		{
			$this->queueSave();
		}
		return $this;
	}

	function current()
	{
		return array_values($this->data)[$this->current];
	}

	function next()
	{
		$this->current++;
	}

	function key()
	{
		return array_keys($this->data)[$this->current];
	}

	function valid()
	{
		return $this->current < count($this->data);
	}

	function rewind()
	{
		$this->current = 0;
	}

	function offsetExists($offset)
	{
		return array_key_exists($offset, $this->data);
	}

	function offsetGet($offset)
	{
		return @$this->data[$offset];
	}

	function offsetSet($offset, $value)
	{
		assert($offset !== null);
		$this->set($offset, $value);
	}

	function set(string $key, $value): Configuration
	{
		$this->data[$key] = $value;
		return $this->queueSave();
	}

	function offsetUnset($offset)
	{
		$this->unset($offset);
	}

	function unset(string $key): Configuration
	{
		unset($this->data[$key]);
		return $this->queueSave();
	}

	function count()
	{
		return count($this->data);
	}
}

Configuration::$save_queue = new SplObjectStorage();
