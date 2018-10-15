## PHPSess
PHP Session. The way it should be.

[![Build Status](https://travis-ci.com/phpsess/session-handler.svg?branch=master)](https://travis-ci.com/phpsess/session-handler)
[![License](https://img.shields.io/github/license/phpsess/session-handler.svg)](https://opensource.org/licenses/MIT)
[![Maintainability](https://api.codeclimate.com/v1/badges/17ffcc017acb054fd644/maintainability)](https://codeclimate.com/github/phpsess/session-handler/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/17ffcc017acb054fd644/test_coverage)](https://codeclimate.com/github/phpsess/session-handler/test_coverage)

__----- STILL IN DEVELOPMENT -----__

PHPSess is a fully featured PHP Session Handler. Anyone can write a new driver to it, making it
a breeze to store the session data in __[ New Shiny and Fast DB ]__ or secure
the data with __[ New State of Art Encryption Library ]__.

It implements the PHP `SessionHandlerInterface` so that you can use the session as you always did:
the old and good `$_SESSION` superglobal and the `session_` functions. Of course, if you want to
use the `SessionHandler` instance directly (eg. in the new shiny framework you're building),
that's fine too.

## What it does
- Encrypts the session data in such a way that even if you have access to the session files,
the source code AND the app-key, you wouldn't be able to decrypt it;
- Prevents session fixation: if a non-existent session-id is given, a new one is generated
instead of accepting arbitrary ids from the request;
- Session locking: if two requests try to manipulate the session at the same time,
one will have to wait for the session to be unlocked;
- Warn about insecure session ini settings.

## Quick Start
Require the core Session Handler, the [Storage](https://github.com/phpsess/session-handler/wiki/Storage-Drivers)
and [Encryption](https://github.com/phpsess/session-handler/wiki/Encryption-Drivers) drivers:

```
composer require phpsess/session-handler phpsess/file-storage phpsess/openssl-encryption
```

Init the drivers and pass them to the Session Handler:

```
use PHPSess\SessionHandler;
use PHPSess\Storage\FileStorage;
use PHPSess\Encryption\OpenSSlEncryption;

$sessEncryption = new OpenSSLEncryption('a-strong-random-SECRET-app-key');

$sessStorage = new FileStorage();
```

Create a instance of the Session Handler and register it to the PHP engine:
```
$sessionHandler = new SessionHandler($sessEncryption, $sessStorage);

session_set_save_handler($sessionHandler);
```

After registering you can use the build in `session_` functions `$_SESSION` superglobal as always:

```
session_start();

$_SESSION['pass'] = 'mySecretP@ss123';
echo $_SESSION['pass'];
```