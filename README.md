# Phpcraft

A PHP library for all things Minecraft. Supports 1.8 - 1.13.2 protocol and includes a chat client, server, and listping utility.

*PHP's Windows Port is not supported due to [a bug](https://bugs.php.net/bug.php?id=34972). Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).*

## Usage

First, you'll need to clone this repository, and [download or install Composer](https://getcomposer.org/download/), and then run `composer install` in this directory.

If you're having issues getting mbcrypt installed, run these commands:

    sudo apt-get -y install gcc make autoconf libc-dev pkg-config php-dev libmcrypt-dev
    sudo pecl install mcrypt-1.0.1
    
If you're still having dependency issues, run `composer install --no-dev`; however, note that you won't be able to join or create servers in online mode if you use this method.

Now, you can simply run the `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. The client has a couple of built-in commands — type `.help` in it for more information.

## For Developers

You can get use Phpcraft as a library by creating a `composer.json` with this content:

    {
       	"repositories": [
      		{
     			"type": "vcs",
     			"url": "https://github.com/timmyRS/Phpcraft"
      		}
       	],
       	"require": {
      		"timmyrs/phpcraft": "dev-master"
       	}
    }

and then running `composer install` (see [Usage](#usage) for help). Finally, you can  `require "vendor/autoload.php";` to [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).
