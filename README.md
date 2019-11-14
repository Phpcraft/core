# Phpcraft [![Build Status](https://travis-ci.org/Phpcraft/core.svg?branch=master)](https://travis-ci.org/Phpcraft/core)

A PHP library for [all things](https://phpcraft.de/docs/inherits.html) Minecraft: Java Edition.

## Prerequisites

You'll need PHP (CLI), Composer, and Git.

### Instructions

- **Debian**: `apt-get -y install php-cli composer git`
- **Windows**:
  1. Install [Cone](https://getcone.org), which will install the latest PHP with it.
  2. Run `cone get composer` as administrator.
  3. Install [Git for Windows](https://git-scm.com/download/win).

## Using the Phpcraft CLI utilities

First, we'll clone the repository and generate the autoload script:

    git clone https://github.com/Phpcraft/core Phpcraft
    cd Phpcraft
    composer install --no-dev --no-suggest --ignore-platform-reqs

Next, we'll run a self check:

    php selfcheck.php

If any dependencies are missing, follow the instructions, and then run the self check again.

Finally, you can use the Phpcraft CLI utilities:

- `php client.php help` — A chat client with basic plugin support and built-in commands; type `.help` for more information.
- `php proxy.php help` — A proxy with plugin support allowing you to play as another account.
- `php listping.php` — A listping utility.
- `php snbt.php` — A tool to convert SNBT.
- `php nbt.php` — A tool to read binary NBT files.
- `php hex2bin.php` — A tool to convert hexadecimal strings and files to their binary representation.
- `php bin2hex.php` — A tool to convert binary strings and files to their hexadecimal representation.
- `php uuid.php` — A tool to convert UUIDs.
- `php packets.php` — A tool to print packets from a binary file, e.g. recorded by WorldSaver.
- `php cache.php` — An interface to manage Phpcraft's resource cache.

Here's an example chain to convert SNBT to a binary NBT file:

```Bash
php snbt.php "{Level: 9001}" | tail -n 1 | php hex2bin.php > nbt.bin
```

## Using Phpcraft as a library

Thanks to Composer, using Phpcraft as a library is really easy. Just head into your project folder and run:

    composer require timmyrs/phpcraft:dev-master --no-suggest --ignore-platform-reqs

Next, we'll run a self check:

    php vendor/timmyrs/phpcraft/selfcheck.php

If any dependencies are missing, follow the instructions, and then run the self check again.

Finally, you can `require "vendor/autoload.php";` to use Phpcraft's many APIs.

In addition to the CLI utilities above and the "Projects using Phpcraft" section below serving up great example code, there's also the [docs](https://phpcraft.de/docs/index.html) and [wiki](https://github.com/timmyrs/Phpcraft/wiki) for you to read.

## Projects using Phpcraft

- [Phpcraft Server](https://github.com/Phpcraft/server): A server based on Phpcraft's integrated server.
- [mcverify](https://github.com/timmyRS/mcverify): A REST API for linking your users' Minecraft accounts.

## Thanks

- Thanks to [wiki.vg](https://wiki.vg/) and the people who're maintaining it.
- Thanks to [Jetbrains](https://www.jetbrains.com/?from=Phpcraft) for providing me with an open-source license for [PhpStorm](https://www.jetbrains.com/phpstorm/?from=Phpcraft) — it's a lovely PHP IDE, and made working on this project much easier.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
