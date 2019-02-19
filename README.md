# Phpcraft [![Build Status](https://travis-ci.org/timmyrs/Phpcraft.svg?branch=master)](https://travis-ci.org/timmyrs/Phpcraft)

A PHP library for all things Minecraft. Includes a pre-made client, server, and listping utility.

## Dependencies

Windows might work for some features, but it's not supported due to [a bug](https://bugs.php.net/bug.php?id=34972) and a general lack of features. Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).

Phpcraft has different dependencies for different use cases, but in general, you'll need PHP-CLI, mbstring, and GMP:

    sudo apt-get -y install php php-cli php-mbstring php-gmp

If you want to join or host an online mode server, you'll also need OpenSSl and mcrypt:

    sudo apt-get -y install openssl php-dev php-xml gcc make autoconf libc-dev pkg-config libmcrypt-dev php-pear
    sudo pecl install mcrypt-1.0.1

## Using the Phpcraft client, server, or listping utility

    # Cloning the repository:
    git clone git@github.com:timmyRS/Phpcraft
    cd Phpcraft
    # Showing information about available commands and arguments:
    php client.php help
    php server.php help
    php listping.php

The client also has a couple of built-in commands — type `.help` in it for more information. Enjoy!

## Using Phpcraft as a library

Thanks to [Composer](https://getcomposer.org/), using Phpcraft as a library is really easy. Just head into your project folder, run `composer require timmyrs/phpcraft:dev-master`, and that's it; you can now `require "vendor/autoload.php";` to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).
