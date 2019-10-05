<?php
if(!class_exists("GamemodeArgument"))
{
	class GamemodeArgument
	{
		public $gamemode;

		function __construct(int $gamemode)
		{
			$this->gamemode = $gamemode;
		}
	}
}
