# SSess
A PHP session handler that encrypts the session data

## What it is
This package secures the session data in such a way that in a situation that a hacker gain access to the server, they would not be able to read or tamper the session data, even if they have access to the session files.

## How to use
Require this package with composer:

```
composer require ayrtonvwf/ssess
```

This package implements the PHP `SessionHandlerInterface`, so you just have to set it as the current session handler:

```
use Ssess\Ssess;

$Ssess = new Ssess;

session_set_save_handler($Ssess, true);
```

After registering, you can use the `$_SESSION` superglobal as always:

```
session_start();

$_SESSION['pass'] = 'mySecretP@ss123';
echo $_SESSION['pass'];
```

## How does it work?
- It hashes the `PHPSESSID` and uses the hash as the name of the file that stores the session data;
- It encrypts the session data, using the `PHPSESSID` as key (the raw value, not the hash created before).

This way, you have to know the right `PHPSESSID` to be able to find the right file and decrypt de info. 