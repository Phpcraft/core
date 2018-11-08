<?php
namespace Phpcraft;
require_once __DIR__."/validate.php"; 
/** A utility for interfacing with the user. */
class UserInterface
{
	private $title;
	private $optional_info;
	private $stdin;
	/**
	 * The string displayed before the user's input, e.g. `$ `
	 * @var string $input_prefix
	 */
	public $input_prefix = "";
	private $input_buffer = "";
	private $cursorpos = 1;
	/**
	 * The function called when the user presses the tabulator key with the currently selected word as parameter. The return should be an array of possible completions.
	 * @var function $tabcomplete_function
	 */
	public $tabcomplete_function = null;
	private $screen_scroll = 0;
	private $chat_log = [];
	private $next_render = 0;

	/**
	 * Returns an array of dependencies required for spinning up a UI which are missing on the system.
	 * To spin up a UI, users need tput and readline, which _should_ be available because WINNT is no longer supported.
	 * Regardless, make sure the return of this function is an empty array before you initalize a UserInterface.
	 * @return array
	 */
	static function getMissingDependencies()
	{
		$dependencies = [];
		$res = trim(shell_exec("tput cols"));
		if($res != intval($res))
		{
			array_push($dependencies, "tput");
		}
		if(!extension_loaded("readline"))
		{
			array_push($dependencies, "readline");
		}
		return $dependencies;
	}

	/**
	 * The constructor.
	 * Note that from this point forward user input is in the hands of the UI until UserInterface::dispose() is called.
	 * @param string $title The title displayed at the top left.
	 * @param string $optional_info Displayed at the top right, if possible.
	 */
	function __construct($title, $optional_info = "")
	{
		$this->title = $title;
		$this->optional_info = $optional_info;
		echo "\x1B[2J";
		$this->stdin = fopen("php://stdin", "r");
		stream_set_blocking($this->stdin, false);
		readline_callback_handler_remove();
		readline_callback_handler_install("", function(){}); // This allows reading STDIN on a char-by-char basis, instead of a line-by-line basis.
		set_error_handler(function($severity, $message, $file, $line)
		{
			if(error_reporting() & $severity)
			{
				$this->add("{$message} at {$file}:{$line}")->render();
			}
		});
		set_exception_handler(function($e)
		{
			$this->add("{$e->getMessage()} (".get_class($e).") at {$e->getFile()}:{$e->getLine()}")->render();
		});
	}

	/**
	 * Disposes of the UI.
	 * @return void
	 */
	function dispose()
	{
		fclose($this->stdin);
		readline_callback_handler_remove();
	}

	/**
	 * Renders the UI.
	 * @param boolean $return_input Set to true when you are ready to potentially receive a string as return.
	 * @return void|string If the user has submitted something and $return_input is true, a string containing their message is returned.
	 */
	function render($return_input = false)
	{
		$read = [$this->stdin];
		$null = null;
		if(stream_select($read, $null, $null, 0))
		{
			while(($char = fgetc($this->stdin)) !== FALSE)
			{
				if($char == "\n")
				{
					if($this->input_buffer == "")
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						if(!$return_input)
						{
							break;
						}
						$line = trim($this->input_buffer);
						$this->input_buffer = "";
						$this->cursorpos = 1;
						$this->next_render = 0;
						return $line;
					}
				}
				else if($char == "\x7F") // Backspace
				{
					if($this->cursorpos == 1 || $this->input_buffer == "")
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						$this->cursorpos--;
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, NULL, "utf-8");
						$this->next_render = 0;
					}
				}
				else if($char == "\t") // Tabulator
				{
					$tabcomplete_function = $this->tabcomplete_function;
					if($tabcomplete_function == NULL)
					{
						echo "\x07"; // Bell/Alert
					}
					else
					{
						$buffer_ = "";
						$completed = false;
						foreach(explode(" ", $this->input_buffer) as $word)
						{
							if($completed)
							{
								$buffer_ .= " ".$word;
								continue;
							}
							if(mb_strlen($buffer_, "utf-8") + mb_strlen($word, "utf-8") + 2 < $this->cursorpos)
							{
								if($buffer_ == "")
								{
									$buffer_ = $word;
								}
								else
								{
									$buffer_ .= " ".$word;
								}
								continue;
							}
							$res = $tabcomplete_function($word);
							if(count($res) == 1)
							{
								if($buffer_ == "")
								{
									$buffer_ = $res[0];
								}
								else
								{
									$buffer_ .= " ".$res[0];
								}
								$this->cursorpos += strlen($res[0]) - mb_strlen($word, "utf-8");
							}
							else
							{
								if(count($res) > 1)
								{
									$this->add(join(", ", $res));
								}
								if($buffer_ == "")
								{
									$buffer_ = $word;
								}
								else
								{
									$buffer_ .= " ".$word;
								}
							}
							$completed = true;
						}
						if($this->cursorpos > mb_strlen($buffer_, "utf-8") + 1)
						{
							$this->cursorpos = mb_strlen($buffer_, "utf-8") + 1;
						}
						$this->input_buffer = $buffer_;
						$this->next_render = 0;
					}
				}
				else
				{
					if($char == "\x1B")
					{
						$char = "^";
					}
					$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").$char.mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
					$this->cursorpos++;
					if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[A") // Arrow Up
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						$this->screen_scroll++;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[B") // Arrow Down
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						$this->screen_scroll--;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[C") // Arrow Right
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						if($this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$this->cursorpos++;
						}
					}
					else if(substr($this->input_buffer, $this->cursorpos - 4, 3) == "^[D") // Arrow Left
					{
						$this->input_buffer = substr($this->input_buffer, 0, $this->cursorpos - 4).substr($this->input_buffer, $this->cursorpos - 1);
						$this->cursorpos -= 3;
						if($this->cursorpos == 1)
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$this->cursorpos--;
						}
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[1~") // Pos1
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = 1;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[3~") // Delete
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, NULL, "utf-8");
						if($this->input_buffer == "" || $this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							echo "\x07"; // Bell/Alert
						}
						$this->cursorpos -= 4;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[4~") // End
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[5~") // Screen Up
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						$this->screen_scroll++;
					}
					else if(substr($this->input_buffer, $this->cursorpos - 5, 4) == "^[6~") // Screen Down
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						$this->screen_scroll--;
					}
					$this->next_render = 0;
				}
			}
		}
		if($this->next_render < microtime(true))
		{
			$width = intval(trim(shell_exec("tput cols")));
			$height = intval(trim(shell_exec("tput lines")));
			echo "\x1B[1;1H\x1B[30;107m{$this->title}";
			$len = mb_strlen($this->title, "utf-8");
			if($width > ($len + mb_strlen($this->optional_info, "utf-8")))
			{
				echo str_repeat(" ", $width - ($len + mb_strlen($this->optional_info, "utf-8"))).$this->optional_info;
			}
			else if($width > $len)
			{
				echo str_repeat(" ", $width - $len);
			}
			echo "\x1B[97;40m".str_repeat(" ", ($height - 1) * $width)."\x1B[1;2H";
			$gol_tahc = array_reverse($this->chat_log);
			$input_height = floor(mb_strlen($this->input_prefix.$this->input_buffer, "utf-8") / $width);
			$j = $this->screen_scroll;
			if($j > 100 - $height)
			{
				$this->screen_scroll = $j = 100 - $height;
				echo "\x07"; // Bell/Alert
			}
			else if($j < ($height - $input_height - 3) * -1)
			{
				$this->screen_scroll = $j = ($height - $input_height - 3) * -1;
				echo "\x07"; // Bell/Alert
			}
			for($i = $height - $input_height - 1; $i > 1; $i--)
			{
				$message = @$gol_tahc[$j++];
				$len = mb_strlen(preg_replace('/\x1B\[[0-9]{1,3}(\;[0-9]{1,3})*m/i', "", $message), "utf-8");
				if($len > $width)
				{
					$i -= floor(mb_strlen($message, "utf-8") / $width);
				}
				echo "\x1B[{$i};1H";
				echo "{$message}\x1B[97;40m";
			}
			echo "\x1B[".($height - $input_height).";1H\x1B[97;40m".$this->input_prefix.$this->input_buffer;
			$cursor_width = (mb_strlen($this->input_prefix, "utf-8") + $this->cursorpos);
			if($cursor_width < $width)
			{
				$cursor_height = $height;				
			}
			else
			{
				$overflows = floor(($cursor_width - 1) / $width);
				$cursor_height = $height - $input_height + $overflows;
				if($overflows > 0)
				{
					$cursor_width -= floor($width / $overflows);
				}
			}
			echo "\x1B[{$cursor_height};{$cursor_width}H";
			if(count($this->chat_log) > 100)
			{
				array_shift($this->chat_log);
			}
			$this->next_render = microtime(true) + 0.2;
		}
	}

	/**
	 * Adds a message to the chat log.
	 * @return $this
	 */
	function add($message)
	{
		array_push($this->chat_log, $message);
		return $this;
	}

	/**
	 * Appends to the last message in the chat log.
	 * @return $this
	 */
	function append($appendix)
	{
		$this->chat_log[count($this->chat_log) - 1] .= $appendix;
		return $this;
	}
}
