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

- PHP >= 7.0.15
- mbstring

### Additional Dependencies for Online Mode

- gmp
- openssl
- mbcrypt

## Usage

Simply clone this repository and run any file you want — `client.php`, `server.php`, or `listping.php`. You can also provide arguments to the client and server — get a list of supported arguments using `php <file>.php help`.

### Built-in Client Commands

The client has a couple of built-in commands, which start with a period (`.`) — type `.help` for a list of them. If you want to send a message starting with a period, use two periods.
