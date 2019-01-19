# Phpcraft

A PHP library for Minecraft and more. Supports 1.8 - 1.13.2 protocol and includes a chat client, server, and listping utility.

## Usage

If you're a PHP developer, feel free to clone this repository to use [the many available APIs](https://timmyrs.github.io/Phpcraft/namespacePhpcraft.html). You can use `require_once "src/autoload.php";` to have all APIs at your disposal or `require_once "src/<class>.class.php";` if you only need specific classes.

Otherwise, feel free to use the pre-made `client.php`, `server.php`, and `listping.php`. You can also provide arguments to the client and server; get a list of possible arguments using `php <file> help`. Also, the client has a couple of built-in commands — type `.help` in it for more information.

## Dependencies

Please note that bare Windows is not supported due to implementation bugs in PHP's Windows port. Instead, use [the Windows Subsystem for Linux](https://aka.ms/wslinstall).

To use Phpcraft's networking features, you need 64-bit PHP-CLI 7.0.15 or higher.

Many parts of Phpcraft require mbstring, which you can install using `sudo apt-get install php-mbstring`.

Aditionally, if you want to join or create an online/premium server, you'll need GMP, OpenSSL, and mcrypt.
To install them, run these commands:

    sudo apt-get -y install php-gmp openssl gcc make autoconf libc-dev pkg-config php-dev libmcrypt-dev
    sudo pecl install mcrypt-1.0.1

If you're still facing issues with dependencies, make sure the modules are loaded in your PHP configuration.