# PHP Minecraft Client

A Minecraft chat client written in PHP.

## Why would anyone ever do this?

I just wanted to challenge myself to create a Minecraft chat client using only one thread and no classes.

## Features

- Colorful messages using ANSI escape codes
- Can join 1.8 - 1.13.1 servers by protocol hacking
- Supports optionally logging in with Mojang and unmigrated accounts to join online servers as well
- Resolves SRV records

## Usage

Simply clone this repository and run `php php-minecraft-client.php`. You can also provide optional arguments — use `php php-minecraft-client.php help` for a list of them.

Once the client is running, you can send messages by typing them, but there are also commands, starting with a period (`.`) — type `.help` for a list of them. If you want to send a message starting with a period, use two.
