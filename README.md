# Phpcraft

## Features

- Colorful display of chat messages using ANSI escape codes
- 1.8 - 1.13.2 support by protocol hacking
- Supports online and offline mode
- Client features:
  - Can follow player's entities
  - Resolves SRV records
- Server features:
  - You can chat with other connected players
- Planned features:
  - See other players on the server in the player list
  - Legacy List Ping

## Dependencies

You will need 64-bit CLI PHP 7.0.15 or above, and mbstring:

    apt-get install php-cli php-mbstring

Aditionally, if you want to go online, you'll need GMP, OpenSSL, and mcrypt:

    apt-get install php-gmp openssl php-mcrypt

If you're on Windows, use [Cygwin](https://www.cygwin.com/) or similar.

## Usage

If you're a PHP developer, feel free to clone this repository, `require "src/autoload.php";`, and feel free to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html).

Otherwise, feel free to use the pre-made `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. Also, the client has a couple of built-in commands — type `.help` in it for more information.
