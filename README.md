# PHP Minecraft Client

A Minecraft chat client written in PHP.

## Why would anyone ever do this?

I just wanted to challenge myself to create a Minecraft chat client using only one thread and no classes.

## Features

- SRV records
- ANSI color codes
- Fully supports `translate` messages (given a language file was provided)

### Planned Features

- Protocol Hacking — currently the client can only join 1.13.1 servers
- Login — currently the client can only join cracked servers

## Usage

**Note: PHP Minecraft Client does not work on Windows due to [PHP bug #34972](https://bugs.php.net/bug.php?id=34972).**

Simply clone this repository and run `php php-minecraft-client.php`. You can also provide optional arguments — use `php php-minecraft-client.php help` for a list of them.

Once the client is running, you can send messages by typing them, but there are also commands, starting with a period (`.`) — type `.help` for a list of them. If you want to send a message starting with a period, use two.
