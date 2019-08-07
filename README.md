# Phpcraft [![Build Status](https://travis-ci.org/timmyrs/Phpcraft.svg?branch=master)](https://travis-ci.org/timmyrs/Phpcraft)

A PHP library for [all things](https://phpcraft.de/docs/inherits.html) Minecraft: Java Edition.

## Dependencies

There are different dependencies for different use cases — the `selfcheck.php` can help you find out what you need for what — but in general, you will need [PHP-CLI](https://www.php.net/downloads.php), [Composer](https://getcomposer.org/download/), and [Git](https://git-scm.com/downloads).
If you're apt to it, feel free to run `sudo apt-get -y install php-cli composer git` to install them.

## Using the Phpcraft CLI utilities

First, we'll clone the repository and generate the autoload script:

    git clone https://github.com/timmyrs/Phpcraft
    cd Phpcraft
    composer install --no-dev --no-suggest --ignore-platform-reqs

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
- `php bin2hex.php` — A tool to convert binary files to hex files.
- `php hex2bin.php` — A tool to convert hex files to binary files.
- `php uuid.php` — A tool to convert UUIDs.
- `php snbt.php` — A tool to convert SNBT.

Here's an example chain to convert SNBT to a binary NBT file (in this case, `snbt.hex.bin`):

```Bash
php snbt.php "{Level: 9001}" | tail -n 1 > snbt.hex && php hex2bin.php snbt.hex
```

## Using Phpcraft as a library

Thanks to Composer, using Phpcraft as a library is really easy. Just head into your project folder and run:

    composer require timmyrs/phpcraft:dev-master --ignore-platform-reqs

Next, we'll run a self check:

    php vendor/timmyrs/phpcraft/selfcheck.php

If any dependencies are missing, run the given command(s), and then run the self check again.

Finally, you can `require "vendor/autoload.php";` to use Phpcraft's many APIs.

In addition to the CLI utilities above and the "Who uses Phpcraft?" section below serving up great example code, there's also the [docs](https://phpcraft.de/docs/index.html) and [wiki](https://github.com/timmyrs/Phpcraft/wiki) for you to read.

## Who uses Phpcraft?

Who would be a crazy enough to use a PHP Minecraft library? Its author of course!

- [mcverify](https://github.com/timmyrs/mcverify): A simple REST API for linking your users' Minecraft: Java Edition accounts.

Also, I'd like to thank [Jetbrains](https://www.jetbrains.com/?from=Phpcraft) for providing me with an open-source license for [PhpStorm](https://www.jetbrains.com/phpstorm/?from=Phpcraft), a lovely PHP IDE.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
