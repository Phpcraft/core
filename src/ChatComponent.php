<?php /** @noinspection PhpUnused */
namespace Phpcraft;
use Exception;
use InvalidArgumentException;
use RuntimeException;
/**
 * @since 0.4
 */
class ChatComponent
{
	const FORMAT_NONE = 0;
	const FORMAT_ANSI = 1;
	/**
	 * §-format
	 */
	const FORMAT_SILCROW = 2;
	/**
	 * &-format
	 */
	const FORMAT_AMPERSAND = 3;
	const FORMAT_HTML = 4;
	/**
	 * @var array<string,string> $translations
	 */
	public static $translations;
	/**
	 * @var array<string,string> $hex_to_color
	 */
	public static $hex_to_color;
	/**
	 * @var array<string,string> $color_to_hex
	 */
	public static $color_to_hex;
	/**
	 * @var array<string,string> $color_to_ansi
	 */
	public static $color_to_ansi;
	/**
	 * @var array<string,string> $color_to_rgb_hex
	 */
	public static $color_to_rgb_hex;
	/**
	 * @var array $attributes
	 */
	public static $attributes;
	/**
	 * @var array<string,string> $attributes_legacy
	 */
	public static $attributes_legacy;
	/**
	 * @var array<string,string> $attributes_ansi
	 */
	public static $attributes_ansi;
	/**
	 * @var array<string,array> $attributes_html
	 */
	public static $attributes_html;
	/**
	 * @var string|null $text
	 */
	public $text;
	/**
	 * @var string|null $color
	 */
	public $color = null;
	/**
	 * @var bool $bold
	 */
	public $bold = false;
	/**
	 * @var bool $italic
	 */
	public $italic = false;
	/**
	 * @var bool $underlined
	 */
	public $underlined = false;
	/**
	 * @var bool $strikethrough
	 */
	public $strikethrough = false;
	/**
	 * @var bool $obfuscated
	 */
	public $obfuscated = false;
	/**
	 * @var $extra ChatComponent[]
	 */
	public $extra = [];
	/**
	 * @var string|null $translate
	 */
	public $translate = null;
	/**
	 * @var $with ChatComponent[]
	 */
	public $with = [];
	/**
	 * @var string|null $keybind
	 * @since 0.4.1
	 */
	public $keybind = null;
	/**
	 * Text to be inserted into the client's chat box when they shift-click the ChatComponent.
	 *
	 * @var string|null $insertion
	 */
	public $insertion = null;
	/**
	 * @var array|null $click_event
	 */
	public $click_event = null;

	private function __construct(?string $text)
	{
		$this->text = $text;
	}

	/**
	 * Downloads the latest supported Minecraft version's translation for the given language into ChatComponent::$translations, so messages using a "translate" component will be displayed correctly.
	 * Note that we can't use en_US because that is compiled into Minecraft's jar and not (legally) accessible otherwise.
	 *
	 * @param string $language_code
	 * @return bool True if the translations for the given language have been applied.
	 */
	static function downloadTranslations(string $language_code = "en_GB"): bool
	{
		try
		{
			$am = AssetsManager::latest();
			$local_file = $am->downloadAsset("minecraft/lang/".strtolower($language_code).".json");
			if($local_file !== null)
			{
				self::$translations = json_decode(file_get_contents($local_file), true);
				return true;
			}
		}
		catch(Exception $ignored)
		{
		}
		return false;
	}

	/**
	 * Instantiates a blank ChatComponent that only serves to contain other ChatComponent instances.
	 *
	 * @param $children ChatComponent[]
	 * @return ChatComponent
	 * @since 0.4.1
	 */
	static function container(ChatComponent...$children): ChatComponent
	{
		$chat = new ChatComponent(null);
		$chat->extra = $children;
		return $chat;
	}

	/**
	 * Instantiates a ChatComponent with the given text.
	 * If the text has § format codes, they will be applied to the ChatComponent.
	 *
	 * @param string $text
	 * @param bool $allow_amp If true, '&' will be handled like '§'.
	 * @return ChatComponent
	 */
	static function text(string $text, bool $allow_amp = false): ChatComponent
	{
		return self::fromText($text, $allow_amp);
	}

	private static function fromText(string &$text, bool $allow_amp): ChatComponent
	{
		if(strpos($text, "§") === false && (!$allow_amp || strpos($text, "&") === false))
		{
			return new ChatComponent($text);
		}
		$components = [
			new ChatComponent("")
		];
		$component = 0;
		$last_was_paragraph = false;
		foreach(preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY) as $c)
		{
			if($c == "§" || ($allow_amp && $c == "&"))
			{
				$last_was_paragraph = true;
			}
			else if($last_was_paragraph)
			{
				$last_was_paragraph = false;
				if($c == "r")
				{
					if($component != 0)
					{
						$components[++$component] = new ChatComponent("");
					}
					continue;
				}
				if($component == 0 || $components[$component]->text != "")
				{
					$components[++$component] = new ChatComponent("");
				}
				if($c == "k")
				{
					$components[$component]->obfuscated = true;
				}
				else if($c == "l")
				{
					$components[$component]->bold = true;
				}
				else if($c == "m")
				{
					$components[$component]->strikethrough = true;
				}
				else if($c == "n")
				{
					$components[$component]->underlined = true;
				}
				else if($c == "o")
				{
					$components[$component]->italic = true;
				}
				else if(array_key_exists($c, self::$hex_to_color))
				{
					$components[$component]->color = self::$hex_to_color[$c];
				}
				else
				{
					$components[$component]->text .= $c;
				}
			}
			else
			{
				$components[$component]->text .= $c;
			}
		}
		if($components[0]->text == "")
		{
			$components[0]->text = null;
		}
		$chat = $components[0];
		if($component > 0)
		{
			if($component == 1 && $chat->text === null)
			{
				$chat = $components[1];
				$chat->extra = array_slice($components, 2);
			}
			else
			{
				$chat->extra = array_slice($components, 1);
			}
		}
		return $chat;
	}

	/**
	 * Instantiates a "translate" ChatComponent.
	 *
	 * @param string $key
	 * @param array $with An array of ChatComponent (or castable) to be used to fill in blanks in the message.
	 * @return ChatComponent
	 */
	static function translate(string $key, array $with = []): ChatComponent
	{
		$chat = new ChatComponent(null);
		$chat->translate = $key;
		foreach($with as $extra)
		{
			array_push($chat->with, ChatComponent::cast($extra));
		}
		return $chat;
	}

	/**
	 * Casts the given value into a ChatComponent.
	 *
	 * @param array|string|null|ChatComponent $value
	 * @return ChatComponent
	 */
	static function cast($value): ChatComponent
	{
		if(is_array($value))
		{
			return ChatComponent::fromArray($value);
		}
		else if(is_string($value) || $value === null)
		{
			return self::fromText($value, false);
		}
		else if(is_object($value))
		{
			if($value instanceof ChatComponent)
			{
				return $value;
			}
			throw new InvalidArgumentException("Can't cast ".get_class($value)." to ChatComponent");
		}
		else
		{
			throw new InvalidArgumentException("Can't cast ".gettype($value)." to ChatComponent");
		}
	}

	static function fromArray(array $array): ChatComponent
	{
		$chat = new ChatComponent(@$array["text"]);
		$chat->translate = @$array["translate"];
		$chat->keybind = @$array["keybind"];
		$chat->insertion = @$array["insertion"];
		$chat->color = @$array["color"];
		foreach(self::$attributes as $attribute)
		{
			if(@$array[$attribute])
			{
				$chat->$attribute = true;
			}
		}
		if(@$array["extra"])
		{
			foreach($array["extra"] as $extra)
			{
				array_push($chat->extra, self::fromArray($extra));
			}
		}
		if(@$array["with"])
		{
			foreach($array["with"] as $extra)
			{
				array_push($chat->with, self::fromArray($extra));
			}
		}
		if(array_key_exists("clickEvent", $array) && is_array($array["clickEvent"]) && array_key_exists("action", $array["clickEvent"]) && array_key_exists("value", $array["clickEvent"]) && in_array($array["clickEvent"]["action"], [
				"open_url",
				"run_command",
				"suggest_command",
				"change_page"
			]))
		{
			$chat->click_event = [
				$array["clickEvent"]["action"],
				$array["clickEvent"]["value"]
			];
		}
		return $chat;
	}

	/**
	 * Initiates a "keybind" ChatComponent.
	 *
	 * @param string $name The name of the key, named after the value in the options.txt, e.g. "key_key.forward" in options.txt would mean "key.forward" here, and "w" would be displayed.
	 * @return ChatComponent
	 * @since 0.4.1
	 */
	static function keybind(string $name): ChatComponent
	{
		$chat = new ChatComponent(null);
		$chat->keybind = $name;
		return $chat;
	}

	/**
	 * Converts the ChatComponent to a string.
	 *
	 * @param int $format The format to apply: <ul><li>0: None (drop colors and attributes)</li><li>1: ANSI escape codes (for compatible terminals)</li><li>2: Paragraph (§) format</li><li>3: Ampersand (&) format</li><li>4: HTML</li></ul>
	 * @return string
	 */
	function toString(int $format = ChatComponent::FORMAT_NONE): string
	{
		if($format < 0 || $format > 4)
		{
			throw new InvalidArgumentException("Invalid format: $format");
		}
		$chat = $this->toArray(true);
		$text = self::toString_($chat, $format, []);
		if($format == self::FORMAT_ANSI)
		{
			$text .= "\e[m";
		}
		return $text;
	}

	/**
	 * @param bool $explicit Explicitly set every property, even if its value can be implied.
	 * @return array
	 */
	function toArray(bool $explicit = false): array
	{
		$chat = [];
		if($this->text !== null)
		{
			if($this->translate !== null)
			{
				throw new RuntimeException("ChatComponent can't have text and translate");
			}
			if($this->keybind !== null)
			{
				throw new RuntimeException("ChatComponent can't have text and keybind");
			}
			$chat["text"] = $this->text;
		}
		else if($this->translate !== null)
		{
			if($this->keybind !== null)
			{
				throw new RuntimeException("ChatComponent can't have translate and keybind");
			}
			$chat["translate"] = $this->translate;
		}
		else if($this->keybind !== null)
		{
			$chat["keybind"] = $this->keybind;
		}
		else if(!$this->extra)
		{
			throw new RuntimeException("ChatComponent needs to have either text, translate, keybind, or extra");
		}
		if($this->color !== null)
		{
			if(!array_key_exists($this->color, self::$color_to_hex))
			{
				throw new RuntimeException("Invalid color: ".$this->color);
			}
			$chat["color"] = $this->color;
		}
		if($explicit)
		{
			foreach(self::$attributes as $attribute)
			{
				$chat[$attribute] = $this->$attribute;
			}
		}
		else
		{
			foreach(self::$attributes as $attribute)
			{
				if($this->$attribute)
				{
					$chat[$attribute] = true;
				}
			}
		}
		if($this->extra)
		{
			if($this->with)
			{
				throw new RuntimeException("ChatComponent can't have extra and with");
			}
			$chat["extra"] = [];
			if($explicit)
			{
				foreach($this->extra as $extra)
				{
					$child = $extra->toArray(true);
					self::explicitChild($chat, $child);
					array_push($chat["extra"], $child);
				}
			}
			else
			{
				foreach($this->extra as $extra)
				{
					array_push($chat["extra"], $extra->toArray(false));
				}
			}
		}
		else if($this->with)
		{
			if(!$this->translate)
			{
				throw new RuntimeException("ChatComponent can't have with without translate");
			}
			$chat["with"] = [];
			if($explicit)
			{
				foreach($this->with as $extra)
				{
					$child = $extra->toArray(true);
					self::explicitChild($chat, $child);
					array_push($chat["with"], $child);
				}
			}
			else
			{
				foreach($this->with as $extra)
				{
					array_push($chat["with"], $extra->toArray(false));
				}
			}
		}
		if($this->insertion !== null)
		{
			$chat["translate"] = $this->insertion;
		}
		return $chat;
	}

	private static function explicitChild(array &$parent, array &$child)
	{
		foreach(self::$attributes as $attribute)
		{
			if(!@$child[$attribute])
			{
				$child[$attribute] = $parent[$attribute];
			}
		}
		if(!@$child["color"])
		{
			$child["color"] = @$parent["color"];
		}
	}

	private static function toString_(array &$chat, int $format, array $previous): string
	{
		$closing_tags = "";
		switch($format)
		{
			/** @noinspection PhpMissingBreakStatementInspection */ case self::FORMAT_SILCROW:
			$prefix = "§";
			case self::FORMAT_AMPERSAND:
				$prefix = $prefix ?? "&";
				$text = (@$previous["bold"] || @$previous["underlined"] || @$previous["italic"] || @$previous["strikethrough"] || @$previous["obfuscated"]) ? $prefix."r" : "";
				foreach(self::$attributes_legacy as $n => $v)
				{
					if(@$chat[$n])
					{
						$text .= $prefix.$v;
					}
				}
				if(@$chat["color"] !== null && $chat["color"] != @$previous["color"])
				{
					$text .= $prefix.self::$color_to_hex[$chat["color"]];
				}
				break;
			case self::FORMAT_HTML:
				$text = "";
				foreach(self::$attributes_html as $n => $v)
				{
					if($chat[$n])
					{
						$text .= $v[0];
						$closing_tags .= $v[1];
					}
				}
				if(@$chat["color"] !== null)
				{
					$text .= '<span style="color:#'.self::$color_to_rgb_hex[$chat["color"]].'">';
					$closing_tags .= "</span>";
				}
				break;
			case self::FORMAT_ANSI:
				$ansi_modifiers = [];
				foreach(self::$attributes_ansi as $n => $v)
				{
					if(@$chat[$n])
					{
						array_push($ansi_modifiers, $v);
					}
				}
				if(@$chat["color"] !== null)
				{
					array_push($ansi_modifiers, self::$color_to_ansi[$chat["color"]]);
				}
				$text = "\x1B[".join(";", $ansi_modifiers)."m";
				break;
			default:
				$text = "";
		}
		if(@$chat["translate"] !== null)
		{
			if(array_key_exists(self::$translations, $chat["translate"]))
			{
				$raw = self::$translations[$chat["translate"]];
				if(@$chat["with"])
				{
					$with = [];
					$previous = $chat;
					foreach($chat["with"] as $extra)
					{
						array_push($with, self::toString_($extra, $format, $previous));
						$previous = $extra;
					}
					if(($formatted = @vsprintf($raw, $with)) !== false)
					{
						$text .= $formatted;
					}
					else
					{
						$text .= $raw;
					}
				}
			}
			else
			{
				$text .= $chat["translate"];
			}
		}
		else if(@$chat["keybind"] !== null)
		{
			$text .= $chat["keybind"];
		}
		else
		{
			$text .= $chat["text"];
		}
		if(@$chat["extra"])
		{
			$previous = $chat;
			foreach($chat["extra"] as $extra)
			{
				$text .= self::toString_($extra, $format, $previous);
				$previous = $extra;
			}
		}
		if($format == self::FORMAT_HTML)
		{
			$text .= $closing_tags;
		}
		return $text;
	}

	/**
	 * Casts $chat to a ChatComponent and adds it to $this->extra.
	 *
	 * @param array|string|null|ChatComponent $chat
	 * @return ChatComponent $this
	 */
	function add($chat): ChatComponent
	{
		$chat = ChatComponent::cast($chat);
		array_push($this->extra, $chat);
		return $this;
	}

	/**
	 * Sets text to be inserted into the client's chat box when they shift-click the ChatComponent.
	 *
	 * @param string $insertion
	 * @return ChatComponent $this
	 */
	function insertion(string $insertion): ChatComponent
	{
		$this->insertion = $insertion;
		return $this;
	}

	/**
	 * When the client clicks on the ChatComponent, the given URL will be opened.
	 * A ChatComponent can only have one click event.
	 *
	 * @param string $url Protocol must be "http" or "https"
	 * @return ChatComponent $this
	 */
	function onClickOpenLink(string $url): ChatComponent
	{
		$this->click_event = [
			"open_url",
			$url
		];
		return $this;
	}

	/**
	 * When the client clicks on the ChatComponent, the given message will be sent in chat.
	 * A ChatComponent can only have one click event.
	 *
	 * @param string $message
	 * @return ChatComponent $this
	 */
	function onClickSendMessage(string $message): ChatComponent
	{
		$this->click_event = [
			"run_command",
			$message
		];
		return $this;
	}

	/**
	 * When the client clicks on the ChatComponent, the given message will be put into their chat box. Only usable in chat messages.
	 * A ChatComponent can only have one click event.
	 *
	 * @param string $message
	 * @return ChatComponent $this
	 */
	function onClickSuggestMessage(string $message): ChatComponent
	{
		$this->click_event = [
			"suggest_command",
			$message
		];
		return $this;
	}

	/**
	 * When the client clicks on the ChatComponent, the given page in the book will be opened, where 1 is the first page. Only usable in books.
	 * A ChatComponent can only have one click event.
	 *
	 * @param int $page
	 * @return ChatComponent $this
	 */
	function onClickChangePage(int $page): ChatComponent
	{
		$this->click_event = [
			"change_page",
			$page
		];
		return $this;
	}

	/**
	 * Sets $this->color to "black".
	 *
	 * @return ChatComponent $this
	 */
	function black(): ChatComponent
	{
		$this->color = "black";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_blue".
	 *
	 * @return ChatComponent $this
	 */
	function dark_blue(): ChatComponent
	{
		$this->color = "dark_blue";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_green".
	 *
	 * @return ChatComponent $this
	 */
	function dark_green(): ChatComponent
	{
		$this->color = "dark_green";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_aqua".
	 *
	 * @return ChatComponent $this
	 */
	function dark_aqua(): ChatComponent
	{
		$this->color = "dark_aqua";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_red".
	 *
	 * @return ChatComponent $this
	 */
	function dark_red(): ChatComponent
	{
		$this->color = "dark_red";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_purple".
	 *
	 * @return ChatComponent $this
	 */
	function dark_purple(): ChatComponent
	{
		$this->color = "dark_purple";
		return $this;
	}

	/**
	 * Sets $this->color to "gold".
	 *
	 * @return ChatComponent $this
	 */
	function gold(): ChatComponent
	{
		$this->color = "gold";
		return $this;
	}

	/**
	 * Sets $this->color to "gray".
	 *
	 * @return ChatComponent $this
	 */
	function gray(): ChatComponent
	{
		$this->color = "gray";
		return $this;
	}

	/**
	 * Sets $this->color to "dark_gray".
	 *
	 * @return ChatComponent $this
	 */
	function dark_gray(): ChatComponent
	{
		$this->color = "dark_gray";
		return $this;
	}

	/**
	 * Sets $this->color to "blue".
	 *
	 * @return ChatComponent $this
	 */
	function blue(): ChatComponent
	{
		$this->color = "blue";
		return $this;
	}

	/**
	 * Sets $this->color to "green".
	 *
	 * @return ChatComponent $this
	 */
	function green(): ChatComponent
	{
		$this->color = "green";
		return $this;
	}

	/**
	 * Sets $this->color to "aqua".
	 *
	 * @return ChatComponent $this
	 */
	function aqua(): ChatComponent
	{
		$this->color = "aqua";
		return $this;
	}

	/**
	 * Sets $this->color to "red".
	 *
	 * @return ChatComponent $this
	 */
	function red(): ChatComponent
	{
		$this->color = "red";
		return $this;
	}

	/**
	 * Sets $this->color to "light_purple".
	 *
	 * @return ChatComponent $this
	 */
	function light_purple(): ChatComponent
	{
		$this->color = "light_purple";
		return $this;
	}

	/**
	 * Sets $this->color to "yellow".
	 *
	 * @return ChatComponent $this
	 */
	function yellow(): ChatComponent
	{
		$this->color = "yellow";
		return $this;
	}

	/**
	 * Sets $this->color to "white".
	 *
	 * @return ChatComponent $this
	 */
	function white(): ChatComponent
	{
		$this->color = "white";
		return $this;
	}

	/**
	 * Sets $this->bold to true.
	 *
	 * @return ChatComponent $this
	 */
	function bold(): ChatComponent
	{
		$this->bold = true;
		return $this;
	}

	/**
	 * Sets $this->strikethrough to true.
	 *
	 * @return ChatComponent $this
	 */
	function strikethrough(): ChatComponent
	{
		$this->strikethrough = true;
		return $this;
	}

	/**
	 * Sets $this->underlined to true.
	 *
	 * @return ChatComponent $this
	 */
	function underline(): ChatComponent
	{
		$this->underlined = true;
		return $this;
	}

	/**
	 * Sets $this->italic to true.
	 *
	 * @return ChatComponent $this
	 */
	function italic(): ChatComponent
	{
		$this->italic = true;
		return $this;
	}

	/**
	 * Sets $this->obfuscated to true.
	 *
	 * @return ChatComponent $this
	 */
	function obfuscate(): ChatComponent
	{
		$this->obfuscated = true;
		return $this;
	}
}

ChatComponent::$translations = [
	"chat.type.text" => "<%s> %s",
	"chat.type.announcement" => "[%s] %s",
	"multiplayer.player.joined" => "%s joined the game",
	"multiplayer.player.left" => "%s left the game"
];
ChatComponent::$hex_to_color = [
	"0" => "black",
	"1" => "dark_blue",
	"2" => "dark_green",
	"3" => "dark_aqua",
	"4" => "dark_red",
	"5" => "dark_purple",
	"6" => "gold",
	"7" => "gray",
	"8" => "dark_gray",
	"9" => "blue",
	"a" => "green",
	"b" => "aqua",
	"c" => "red",
	"d" => "light_purple",
	"e" => "yellow",
	"f" => "white"
];
ChatComponent::$color_to_hex = [
	"black" => "0",
	"dark_blue" => "1",
	"dark_green" => "2",
	"dark_aqua" => "3",
	"dark_red" => "4",
	"dark_purple" => "5",
	"gold" => "6",
	"gray" => "7",
	"dark_gray" => "8",
	"blue" => "9",
	"green" => "a",
	"aqua" => "b",
	"red" => "c",
	"light_purple" => "d",
	"yellow" => "e",
	"white " => "f"
];
ChatComponent::$color_to_ansi = [
	"black" => "30",
	"dark_blue" => "34",
	"dark_green" => "32",
	"dark_aqua" => "36",
	"dark_red" => "31",
	"dark_purple" => "35",
	"gold" => "33",
	"gray" => "37",
	"dark_gray" => "90",
	"blue" => "94",
	"green" => "92",
	"aqua" => "96",
	"red" => "91",
	"light_purple" => "95",
	"yellow" => "93",
	"white" => "97"
];
ChatComponent::$color_to_rgb_hex = [
	"black" => "000",
	"dark_blue" => "0000aa",
	"dark_green" => "00aa00",
	"dark_aqua" => "00aaaa",
	"dark_red" => "aa0000",
	"dark_purple" => "aa00aa",
	"gold" => "ffaa00",
	"gray" => "aaa",
	"dark_gray" => "555",
	"blue" => "5555ff",
	"green" => "55ff55",
	"aqua" => "55ffff",
	"red" => "ff5555",
	"light_purple" => "ff55ff",
	"yellow" => "ffff55",
	"white" => "fff"
];
ChatComponent::$attributes = [
	"bold",
	"strikethrough",
	"underlined",
	"italic",
	"obfuscated"
];
ChatComponent::$attributes_legacy = [
	"obfuscated" => "k",
	"bold" => "l",
	"strikethrough" => "m",
	"underlined" => "n",
	"italic" => "o"
];
ChatComponent::$attributes_html = [
	"bold" => [
		"<b>",
		"</b>"
	],
	"italic" => [
		"<i>",
		"</i>"
	],
	"underlined" => [
		'span style="text-decoration:underline"',
		"</span>"
	],
	"strikethrough" => [
		"<del>",
		"</del>"
	]
];
ChatComponent::$attributes_ansi = [
	"bold" => "1",
	"italic" => "3",
	"underlined" => "4",
	"obfuscated" => "8",
	"strikethrough" => "9"
];
