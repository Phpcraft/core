# Phpcraft

A PHP library for all things Minecraft. Includes a pre-made client, server, and listping utility.

*PHP's Windows Port is not supported due to [a bug](https://bugs.php.net/bug.php?id=34972). Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).*

## Dependencies

For basic usage, all you need is PHP-CLI and mbstring:

	sudo apt-get -y install php-cli php-mbstring

Please note that some network features require 64-bit PHP at version 7.0.15 or above.

If you want to create or join an online mode server, you'll need GMP, OpenSSl, and mcrypt:

    sudo apt-get -y install php-gmp openssl gcc make autoconf libc-dev pkg-config php-dev libmcrypt-dev
    sudo pecl install mcrypt-1.0.1

## Using the Phpcraft client, server, or listping utility

Clone this repository, and then you can run the `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. The client has a couple of built-in commands — type `.help` in it for more information.

## Using Phpcraft as a library

Thanks to [Composer](https://getcomposer.org/), using Phpcraft as a library is really easy. Just head into your project folder, run `composer require timmyrs/phpcraft:dev-master`, and that's it; you can now `require "vendor/autoload.php";` to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).
