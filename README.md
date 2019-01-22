# Phpcraft

A PHP library for all things Minecraft. Supports 1.8 - 1.13.2 protocol and includes a chat client, server, and listping utility.

*PHP's Windows Port is not supported due to [a bug](https://bugs.php.net/bug.php?id=34972). Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).*

## Usage

First, make sure you've got [Composer](https://getcomposer.org/) installed or downloaded at least.

### Using Phpcraft as a library

In your project folder, run `composer require timmyrs/phpcraft:dev-master`, and that's it; you can now  `require "vendor/autoload.php";` to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).

### Using the Phpcraft client, server, or listping utility

Clone this repository, run `composer install`, and then you can run the `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. The client has a couple of built-in commands — type `.help` in it for more information.

## Resolving Dependency Issues

If you're having issues getting mcrypt installed, run these commands:

    sudo apt-get -y install gcc make autoconf libc-dev pkg-config php-dev libmcrypt-dev
    sudo pecl install mcrypt-1.0.1
    
If you're still having dependency issues, run `composer install --no-dev`; however, note that you won't be able to join or create servers in online mode without the "dev" dependencies.
