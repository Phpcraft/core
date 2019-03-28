# Phpcraft [![Travis Build Status](https://travis-ci.org/timmyrs/Phpcraft.svg?branch=master)](https://travis-ci.org/timmyrs/Phpcraft) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/timmyrs/Phpcraft/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/timmyrs/Phpcraft/?branch=master)

A PHP library for [all things](https://phpcraft.de/docs/namespacePhpcraft.html) Minecraft: Java Edition.

## Using the Phpcraft CLI utilities

First, we'll clone the repository and generate the autoload script:

    sudo apt-get -y install php-cli composer git
    git clone https://github.com/timmyrs/Phpcraft
    cd Phpcraft
    composer install

Next, we'll run a self check:

    php selfcheck.php

If any dependencies are missing, run the given command(s), and then run the self check again.

Finally, you can use the Phpcraft CLI utilities:

- `php client.php help` — A chat client with plugin support and built-in commands; type `.help` for more information.
- `php server.php help` — A chat server with plugin support.
- `php proxy.php help` — A proxy allowing you to play as another account.
- `php listping.php` — A listping utility.
- `php cache.php` — An interface to manage Phpcraft's resource cache.
- `php packets.php` — A tool to print packets from a binary file, e.g. recorded by WorldSaver.
- `php uuid.php` — A tool to convert UUIDs.

## Using Phpcraft as a library

Thanks to [Composer](https://getcomposer.org/), using Phpcraft as a library is really easy. Just head into your project folder, and run:

    sudo apt-get -y install composer
    composer require timmyrs/phpcraft:dev-master

Next, we'll run a self check:

    php vendor/timmyrs/phpcraft/selfcheck.php

If any dependencies are missing, run the given command(s), and then run the self check again.

Finally, you can `require "vendor/autoload.php";` to use Phpcraft's many APIs.

In addition to the CLI utilities above and the "Who uses Phpcraft?" section below serving up great example code, there's also a [documentation](https://phpcraft.de/docs/namespacePhpcraft.html) and [wiki](https://github.com/timmyrs/Phpcraft/wiki) for you to read.

## Who uses Phpcraft?

Who would be a crazy enough to use a PHP Minecraft library? Its author of course!

- [mcverify](https://github.com/timmyrs/mcverify): A simple REST API for linking your users' Minecraft: Java Edition accounts.

Also, I'd like to thank [Jetbrains](https://www.jetbrains.com/?from=Phpcraft) for providing me with an open-source license for [PhpStorm](https://www.jetbrains.com/phpstorm/?from=Phpcraft), a lovely PHP IDE.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
