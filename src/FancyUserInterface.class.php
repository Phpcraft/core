<?php
namespace Phpcraft;
class FancyUserInterface extends UserInterface
{
	private $title;
	private $optional_info;
	/**
	 * The string displayed before the user's input, e.g. `$ `
	 * @var string $input_prefix
	 */
	public $input_prefix = "";
	private $input_buffer = "";
	private $cursorpos = 1;
	/**
	 * The function called when the user presses the tabulator key with the currently selected word as parameter. The return should be an array of possible completions.
	 * @var callable $tabcomplete_function
	 */
	public $tabcomplete_function = null;
	private $rendered_title = false;
	private $screen_scroll = 0;
	private $chat_log = [];
	private $chat_log_cap = 100;
	private $next_render = 0;
	private $_width = 0;
	private $_height = 0;

	/**
	 * The constructor.
	 * Note that from this point forward, STDIN and STDOUT are in the hands of the UI until it is destructed.
	 * @param string $title The title displayed at the top left.
	 * @param string $optional_info Displayed at the top right, if possible.
	 */
	function __construct($title, $optional_info = "")
	{
		parent::__construct();
		$this->title = $title;
		$this->optional_info = $optional_info;
		echo "\x1B[2J";
		readline_callback_handler_remove();
		readline_callback_handler_install("", function(){});
		$this->ob_start();
	}

	/**
	 * @copydoc UserInterface::__destruct()
	 */
	function __destruct()
	{
		parent::__destruct();
		readline_callback_handler_remove();
		ob_end_flush();
		echo "\n";
	}

	private function ob_start()
	{
		ob_start(function($buffer)
		{
			foreach(explode("\n", $buffer) as $line)
			{
				if($line = trim($line))
				{
					$this->add($line);
				}
			}
			return "";
		});
	}

	/**
	 * @copydoc UserInterface::render
	 */
	function render($accept_input = false)
	{
		ob_end_flush();
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
						if(!$accept_input)
						{
							break;
						}
						$line = trim($this->input_buffer);
						$this->input_buffer = "";
						$this->cursorpos = 1;
						$this->next_render = 0;
						$this->ob_start();
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
					if($this->tabcomplete_function == NULL)
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
							$res = ($this->tabcomplete_function)($word);
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
								$this->cursorpos += mb_strlen($res[0], "utf-8") - mb_strlen($word, "utf-8");
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
					if($this->cursorpos > mb_strlen($this->input_buffer, "utf-8") + 1)
					{
						$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
					}
					if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[A") // Arrow Up
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 3;
						$this->screen_scroll++;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[B") // Arrow Down
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 3;
						$this->screen_scroll--;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[C") // Arrow Right
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
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
					else if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[D") // Arrow Left
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
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
					else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[1~") // Pos1
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = 1;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[3~") // Delete
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, NULL, "utf-8");
						if($this->input_buffer == "" || $this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							echo "\x07"; // Bell/Alert
						}
						$this->cursorpos -= 4;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[4~") // End
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[5~") // Screen Up
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						$this->screen_scroll++;
					}
					else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[6~") // Screen Down
					{
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, NULL, "utf-8");
						$this->cursorpos -= 4;
						$this->screen_scroll--;
					}
					$this->next_render = 0;
				}
			}
		}
		if(!$accept_input || $this->next_render < microtime(true))
		{
			$width = intval(trim(shell_exec("tput cols")));
			$height = intval(trim(shell_exec("tput lines")));
			if($width != $this->_width || $height != $this->_height)
			{
				$this->rendered_title = false;
			}
			if(!$this->rendered_title)
			{
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
				$this->rendered_title = true;
			}
			$gol_tahc = array_reverse($this->chat_log);
			$input_height = floor(mb_strlen($this->input_prefix.$this->input_buffer, "utf-8") / $width);
			if($this->screen_scroll > ($this->chat_log_cap - $height) + $input_height)
			{
				$this->screen_scroll = ($height * -1) + 3 + $input_height;
				echo "\x07";
			}
			else if($this->screen_scroll < ($height * -1) + 3 + $input_height)
			{
				$this->screen_scroll = ($this->chat_log_cap - $height) + $input_height;
				echo "\x07";
			}
			$j = $this->screen_scroll;
			for($i = $height - $input_height - 1; $i > 1; $i--)
			{
				$message = @$gol_tahc[$j++];
				$len = mb_strlen(preg_replace('/\x1B\[[0-9]{1,3}(\;[0-9]{1,3})*m/i', "", $message), "utf-8");
				if($len > $width)
				{
					$i -= floor($len / $width);
				}
				//echo "\x1B[{$i};1H\x1B[97;40m{$message}\x1B[97;44m";
				echo "\x1B[{$i};1H\x1B[97;40m{$message}";
				$line_len = ($len == 0 ? 0 : ($len - (floor(($len - 1) / $width) * $width)));
				if($line_len < $width)
				{
					echo str_repeat(" ", $width - $line_len);
				}
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
			$line_len = mb_strlen($this->input_prefix.$this->input_buffer, "utf-8") - ($input_height * $width);
			if($line_len < $width)
			{
				echo str_repeat(" ", $width - $line_len);
			}
			echo "\x1B[{$cursor_height};{$cursor_width}H";
			if(count($this->chat_log) > $this->chat_log_cap)
			{
				$this->chat_log = array_slice($this->chat_log, 1);
			}
			$this->next_render = microtime(true) + 0.2;
			$this->_width = $width;
			$this->_height = $height;
		}
		$this->ob_start();
		return null;
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
