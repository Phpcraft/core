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

## Table of Contents

- [Using Phpcraft as a library](#using-phpcraft-as-a-library)
- [Using the Phpcraft Client & Proxy](#using-the-phpcraft-client--proxy)
- [Projects using Phpcraft](#projects-using-phpcraft)
- [Thanks](#thanks)

## Using Phpcraft as a library

Thanks to Composer, using Phpcraft as a library is really easy. Just head into your project folder and run:

```Bash
composer require craft/core --no-suggest --ignore-platform-reqs
```

Next, we'll run a self check:

```Bash
php vendor/craft/core/selfcheck.php
```

If any dependencies are missing, follow the instructions, and then run the self check again.

Finally, you can `require "vendor/autoload.php";` to use Phpcraft's many APIs.

In addition to the CLI utilities above and the "Projects using Phpcraft" section below serving up great example code, there's also the [docs](https://phpcraft.de/docs/index.html) and [wiki](https://github.com/Phpcraft/core/wiki) for you to read.

## Using the Phpcraft Client & Proxy

First, we'll clone the repository and generate the autoload script:

```Bash
git clone https://github.com/Phpcraft/core Phpcraft
cd Phpcraft
composer install --no-dev --no-suggest --ignore-platform-reqs
```

Next, we'll run a self check:

```Bash
php selfcheck.php
```

If any dependencies are missing, follow the instructions, and then run the self check again.

### That's it!

You can now use `php client.php help` and `php proxy.php help` to view the usage for the client and proxy, respectively.

The client has built-in commands; type `.help` in it for more information.

### Updating

To update Phpcraft, the client, the proxy, and their dependencies:

```Bash
git stash
git pull
composer update --no-dev --no-suggest --ignore-platform-reqs
git stash pop
``` 

If you have made local changes, they will be saved and re-applied after the update.

## Projects using Phpcraft

- [Phpcraft Server](https://github.com/Phpcraft/server)
- [Phpcraft Surrogate](https://github.com/Phpcraft/surrogate) — reverse proxy
- [Phpcraft Toolbox](https://github.com/Phpcraft/toolbox) — CLI tools
- [mcverify](https://github.com/timmyRS/mcverify) — a REST API for linking your users' Minecraft accounts

## Thanks

- Thanks to [wiki.vg](https://wiki.vg/) and the people who're maintaining it.
- Thanks to [Jetbrains](https://www.jetbrains.com/?from=Phpcraft) for providing me with an open-source license for [PhpStorm](https://www.jetbrains.com/phpstorm/?from=Phpcraft) — it's a lovely PHP IDE, and made working on this project much easier.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
