# Phpcraft

## Features

- Colorful display of chat messages using ANSI escape codes
- 1.8 - 1.13.2-pre2 support by protocol hacking
- Supports online and offline mode
- Client features:
	- Can follow player's entities
	- Resolves SRV records
- Server features:
	- You can chat with other connected players
- Planned features:
	- Legacy List Ping
	- Display messages using § colorfully as well

## Client Usage

Simply clone this repository and run `php client.php`. You can also provide optional arguments — use `php client.php help` to get list of them.

Once the client is running, you can send messages by typing them, but there are also commands, starting with a period (`.`) — type `.help` for a list of them. If you want to send a message starting with a period, use two periods.
