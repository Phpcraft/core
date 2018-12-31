<?php
if(PHP_INT_SIZE < 8)
{
	die("Phpcraft requires 64-bit PHP.\n");
}
if(version_compare(phpversion(), "7.0.15", "<"))
{
	die("Phpcraft requires PHP 7.0.15 or above. Try `apt-get install php-cli`.\n");
}
if(!extension_loaded("mbstring"))
{
	die("Phpcraft requires mbstring. Try `apt-get install php-mbstring` or check your PHP configuration.\n");
}
