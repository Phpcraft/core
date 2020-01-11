<?php /** @noinspection PhpComposerExtensionStubsInspection */
namespace Phpcraft;
use Asyncore\
{Asyncore, stdin};
use RuntimeException;
class FancyUserInterface extends UserInterface
{
	/**
	 * The function called when the user presses the tabulator key with the currently selected word as parameter. The return should be an array of possible completions.
	 *
	 * @var callable $tabcomplete_function
	 */
	public $tabcomplete_function = null;
	private $input_prefix = "";
	private $stdin;
	private $input_buffer = "";
	private $cursorpos = 1;
	private $screen_scroll = 0;
	private $chat_log = [];
	private $chat_log_cap = 100;
	private $render_loop;

	/**
	 * Note that from this point forward, STDIN and STDOUT are in the hands of the UI with no way out.
	 * Make sure to register a "stdin_line" event handler with pas if you'd like input to work.
	 * A render loop will be registered with pas, but you may still call ::render if you need immediate rendering.
	 * For PHP <7.2.0 and Windows <10.0.10586, use the PlainUserInterface.
	 *
	 * @param string $title The title that the terminal window will be given.
	 */
	function __construct(string $title)
	{
		parent::__construct($title);
		if(Phpcraft::isWindows())
		{
			stdin::init(null, false);
			if(version_compare(PHP_VERSION, "7.2.0", "<") || php_uname("r") != "10.0" || explode(" ", php_uname("v"))[1] < 10586)
			{
				throw new RuntimeException("For PHP <7.2.0 and Windows <10.0.10586, use the PlainUserInterface.");
			}
			sapi_windows_vt100_support(STDOUT, true);
			echo "\e[2J\e[9999H";
		}
		else
		{
			echo "\e]0;$title\x03\e[2J\e[9999H";
			readline_callback_handler_remove();
			readline_callback_handler_install("", function()
			{
			});
			$this->stdin = fopen("php://stdin", "r");
			stream_set_blocking($this->stdin, false);
		}
		$this->ob_start();
		$this->render_loop = Asyncore::addInessential(function()
		{
			$this->render();
		}, 0.2, true);
	}

	private function ob_start(): void
	{
		ob_start(function(string $buffer)
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
	 * Adds a message to be printed on the user interface.
	 *
	 * @param string $message
	 * @return FancyUserInterface $this
	 */
	function add(string $message): FancyUserInterface
	{
		array_push($this->chat_log, $message);
		return $this;
	}

	/**
	 * Renders the UI immediately.
	 */
	function render(): void
	{
		ob_end_flush();
		if($this->stdin !== null)
		{
			$read = [$this->stdin];
			$null = [];
			if(stream_select($read, $null, $null, 0))
			{
				while(($char = fgetc($this->stdin)) !== false)
				{
					if($char == "\n")
					{
						if($this->input_buffer == "")
						{
							echo "\x07"; // Bell/Alert
						}
						else
						{
							$line = trim($this->input_buffer);
							$this->input_buffer = "";
							$this->cursorpos = 1;
							$this->render_loop->next_run = microtime(true);
							$this->ob_start();
							Asyncore::fire("stdin_line", [$line]);
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
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, null, "utf-8");
							$this->render_loop->next_run = microtime(true);
						}
					}
					else if($char == "\t") // Tabulator
					{
						if($this->tabcomplete_function == null)
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
							$this->render_loop->next_run = microtime(true);
						}
					}
					else
					{
						if($char == "\e")
						{
							$char = "^";
						}
						$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 1, "utf-8").$char.mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
						$this->cursorpos++;
						if($this->cursorpos > mb_strlen($this->input_buffer, "utf-8") + 1)
						{
							$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
						}
						if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[A") // Arrow Up
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos -= 3;
							$this->screen_scroll++;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[B") // Arrow Down
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos -= 3;
							$this->screen_scroll--;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 4, 3, "utf-8") == "^[C") // Arrow Right
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
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
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 4, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
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
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos = 1;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[3~") // Delete
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos, null, "utf-8");
							if($this->input_buffer == "" || $this->cursorpos == mb_strlen($this->input_buffer, "utf-8") + 1)
							{
								echo "\x07"; // Bell/Alert
							}
							$this->cursorpos -= 4;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[4~") // End
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos = mb_strlen($this->input_buffer, "utf-8") + 1;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[5~") // Screen Up
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos -= 4;
							$this->screen_scroll++;
						}
						else if(mb_substr($this->input_buffer, $this->cursorpos - 5, 4, "utf-8") == "^[6~") // Screen Down
						{
							$this->input_buffer = mb_substr($this->input_buffer, 0, $this->cursorpos - 5, "utf-8").mb_substr($this->input_buffer, $this->cursorpos - 1, null, "utf-8");
							$this->cursorpos -= 4;
							$this->screen_scroll--;
						}
						$this->render_loop->next_run = microtime(true);
					}
				}
			}
		}
		// In theory this can also be done using ANSI by printing "\e[9999;9999H\e[6n" and then reading from STDIN.
		if(Phpcraft::isWindows())
		{
			$proc = proc_open('"'.realpath(Phpcraft::BIN_DIR.'/get_window_size.exe').'"', [
				0 => [
					"pipe",
					"r"
				],
				1 => [
					"pipe",
					"w"
				],
				2 => [
					"file",
					"php://stdout",
					"w"
				]
			], $pipes);
			$res = stream_get_contents($pipes[1]);
			proc_close($proc);
		}
		else
		{
			$res = shell_exec('echo "$(tput cols);$(tput lines)"');
		}
		$res = explode(";", $res);
		assert(count($res) == 2);
		$width = intval($res[0]);
		$height = intval($res[1]);
		$reversed_chat_log = array_reverse($this->chat_log);
		$input_height = $this->stdin === null ? 1 : floor(mb_strlen($this->input_prefix.$this->input_buffer, "utf-8") / $width);
		if($this->screen_scroll > ($this->chat_log_cap - $height) + $input_height)
		{
			$this->screen_scroll = ($height * -1) + 3 + $input_height;
			echo "\x07"; // Bell/Alert
		}
		else if($this->screen_scroll < ($height * -1) + 3 + $input_height)
		{
			$this->screen_scroll = ($this->chat_log_cap - $height) + $input_height;
			echo "\x07"; // Bell/Alert
		}
		$j = $this->screen_scroll;
		if($this->stdin === null)
		{
			echo "\e[s";
		}
		for($i = $height - $input_height - 1; $i > 1; $i--)
		{
			$message = @$reversed_chat_log[$j++];
			$len = mb_strlen(preg_replace('/\e\[[0-9]{1,3}(;[0-9]{1,3})*m/i', "", $message), "utf-8");
			if($len > $width)
			{
				$i -= floor($len / $width);
			}
			echo "\e[{$i};1H\e[97;40m$message";
			$line_len = ($len == 0 ? 0 : ($len - (floor(($len - 1) / $width) * $width)));
			if($line_len < $width)
			{
				echo str_repeat(" ", intval($width - $line_len));
			}
		}
		echo "\e[".($height - $input_height).";1H";
		if($this->stdin === null)
		{
			echo "\e[2K\n\e[97;40m".$this->input_prefix."\e[u";
		}
		else
		{
			echo "\e[97;40m".$this->input_prefix.$this->input_buffer;
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
				echo str_repeat(" ", intval($width - $line_len));
			}
			echo "\e[{$cursor_height};{$cursor_width}H";
		}
		if(count($this->chat_log) > $this->chat_log_cap)
		{
			$this->chat_log = array_slice($this->chat_log, 1);
		}
		$this->ob_start();
	}

	/**
	 * Sets the string displayed before the user's input
	 *
	 * @param string $input_prefix
	 * @return void
	 */
	function setInputPrefix(string $input_prefix): void
	{
		if($this->stdin === null)
		{
			ob_end_flush();
			echo "\e[".strlen($input_prefix)."C";
			$this->ob_start();
		}
		$this->input_prefix = $input_prefix;
	}

	/**
	 * Appends to the last message.
	 *
	 * @param string $appendix
	 * @return FancyUserInterface $this
	 */
	function append(string $appendix): FancyUserInterface
	{
		$this->chat_log[count($this->chat_log) - 1] .= $appendix;
		return $this;
	}
}
