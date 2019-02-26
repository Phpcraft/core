# Phpcraft [![Build Status](https://travis-ci.org/timmyrs/Phpcraft.svg?branch=master)](https://travis-ci.org/timmyrs/Phpcraft)

A PHP library all about Minecraft: Java Edition.

## Dependencies

Windows might work for some features, but it's not supported due to [a bug](https://bugs.php.net/bug.php?id=34972) and a general lack of features. Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).

Phpcraft has different dependencies for different use cases, but in general, you'll need PHP, mbstring, and GMP:

    sudo apt-get -y install php php-cli php-mbstring php-gmp

If you want to join or host an online mode server, you'll also need OpenSSl and mcrypt. The installation of mcrypt is different depending on your PHP version, so check `php -version`, and then run the appropriate commands:

**PHP 7.2 and above:**

    sudo apt-get -y install openssl php-dev php-xml gcc make autoconf libc-dev pkg-config libmcrypt-dev php-pear
    sudo pecl install mcrypt-1.0.1

**PHP 7.1 and below:**

    sudo apt-get -y install openssl php-mcrypt

## Using the Phpcraft CLI utilities

First, clone the repository:

    git clone git@github.com:timmyRS/Phpcraft
    cd Phpcraft

and then you can run:

- `php client.php help` — A chat client with plugin support and built-in commands; type `.help` for more information.
- `php server.php help` — A chat server with plugin support.
- `php proxy.php` — A proxy allowing you to play as another account.
- `php listping.php` — A listping utility.
- `php cache.php` — An interface to manage Phpcraft's resource cache.
- `php packets.php` — A tool to print packets from a binary file, e.g. recorded by WorldSaver.

Enjoy!

## Using Phpcraft as a library

Thanks to [Composer](https://getcomposer.org/), using Phpcraft as a library is really easy. Just head into your project folder, run `composer require timmyrs/phpcraft:dev-master`, and that's it; you can now `require "vendor/autoload.php";` to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).

## Who uses Phpcraft?

Who would be a crazy enough to use a PHP Minecraft library? Its author of course!

- [mcverify](https://github.com/timmyrs/mcverify): A simple REST API for linking your users' Minecraft: Java Edition accounts.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
