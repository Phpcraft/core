<?php
if(empty($argv))
{
	die("This is for CLI PHP. Connect to your server via SSH and use `php ".get_included_files()[0]."` to execute it.\n");
}
if(PHP_INT_SIZE < 8)
{
	die("Phpcraft requires 64-bit PHP.\n");
}
if(version_compare(phpversion(), "7.0.15", "<"))
{
	die("Phpcraft requires PHP 7.0.15 or above. Try `apt-get install php7.0-cli`.\n");
}
if(!extension_loaded("mbstring"))
{
	die("Phpcraft requires mbstring. Try `apt-get install php-mbstring` or check your PHP configuration.\n");
}
