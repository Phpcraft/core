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

- [Using Phpcraft](#using-phpcraft)
- [Projects using Phpcraft](#projects-using-phpcraft)
- [Thanks](#thanks)

## Using Phpcraft

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

### Modules

You can use modules to extend the functionality of Phpcraft:

- [Realms](https://github.com/Phpcraft/realms)

## Projects using Phpcraft

- [Phpcraft Server](https://github.com/Phpcraft/server)
- [Phpcraft Client](https://github.com/Phpcraft/client)
- [Phpcraft Toolbox](https://github.com/Phpcraft/toolbox) — CLI tools
- [Phpcraft Surrogate](https://github.com/Phpcraft/surrogate) — reverse proxy
- [mcverify](https://github.com/timmyRS/mcverify) — a REST API for linking your users' Minecraft accounts
- [Phpcraft Proxy](https://github.com/Phpcraft/proxy)

## Thanks

- Thanks to [wiki.vg](https://wiki.vg/) and the people who're maintaining it.
- Thanks to [Jetbrains](https://www.jetbrains.com/?from=Phpcraft) for providing me with an open-source license for [PhpStorm](https://www.jetbrains.com/phpstorm/?from=Phpcraft) — it's a lovely PHP IDE, and made working on this project much easier.

---

Phpcraft is not partnered or associated with Microsoft or Mojang.
