# Phpcraft

Interact with the Minecraft Protocol in PHP. Includes a chat client, server, and listping utility.

## Features

- Colorful display of chat messages using ANSI escape codes
- 1.8 - 1.13.2 support by protocol hacking
- Supports online and offline mode
- Ability to resolve SRV records

## Dependencies

Please note that bare Windows is not supported due to implementation bugs in PHP's Windows port. Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).

You will need 64-bit PHP-CLI 7.0.15 or above, and mbstring.
To install them, run these commands as root:

    apt-get -y install php-cli php-mbstring

Aditionally, if you want to go online, you'll need GMP, OpenSSL, and mcrypt.
To install them, run these commands as root:

    apt-get -y install php-gmp openssl php-mcrypt

If `php-mcrypt` does not have an installation candiate, run these commands as root:

    apt-get -y install gcc make autoconf libc-dev pkg-config
    apt-get -y install php7.2-dev
    apt-get -y install libmcrypt-dev
    pecl install mcrypt-1.0.1

If you're still facing issues with dependencies, check your PHP configuration.

## Usage

If you're a PHP developer, feel free to clone this repository, `require "src/autoload.php";`, and feel free to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).

Otherwise, feel free to use the pre-made `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. Also, the client has a couple of built-in commands — type `.help` in it for more information.
