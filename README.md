# PHPSess - Session Handler
PHP Session. The way it should be.

[![Build Status](https://travis-ci.com/phpsess/session-handler.svg?branch=master)](https://travis-ci.com/phpsess/session-handler)
[![License](https://img.shields.io/github/license/phpsess/session-handler.svg)](https://opensource.org/licenses/MIT)
[![Maintainability](https://api.codeclimate.com/v1/badges/17ffcc017acb054fd644/maintainability)](https://codeclimate.com/github/phpsess/session-handler/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/17ffcc017acb054fd644/test_coverage)](https://codeclimate.com/github/phpsess/session-handler/test_coverage)

## What it does
- Encrypts the session data in such a way that even if you have access to the session files,
the source code AND the app-key, you wouldn't be able to decrypt it;
- Prevents session fixation: if a non-existent session-id is given, a new one is generated
instead of accepting arbitrary ids from the request;
- Warn about insecure session ini settings.

## How to use
Require this package with composer:

```
composer require phpsess/session-handler
```

This package implements the PHP `SessionHandlerInterface`, so you just have to set it as the current session handler:

```
use PHPSess\SessionHandler;
use PHPSess\Storage\FileStorage;
use PHPSess\CryptProvider\OpenSSlCryptProvider;

// The driver responsible for encryption, decription and hashing
$cryptProvider = new OpenSSLCryptProvider('a-strong-random-SECRET-app-key');

// The driver responsible for storing the data
$storage = new FileStorage();

// Build the Session Handler
$sessionHandler = new SessionHandler($cryptProvider, $storage);

// Set PHPSess as the defaul session handler
session_set_save_handler($sessionHandler);
```

After registering, you can use the `$_SESSION` superglobal as always:

```
session_start();

$_SESSION['pass'] = 'mySecretP@ss123';
echo $_SESSION['pass'];
```

## How encryption works
- The name of the session file is a hash of the session-id concatenated with the app-key;
- The key used for encryption is a hash of the app-key concatenated with the session-id.

This way, the server doesn't stores the session-ids, and can't decrypt the session files
without a HTTP request providing a valid session-id.

Also, if somebody takes the session files and knows a valid session-id, they wouldn't be
able to decrypt those files without the app-key.

## Before vs after

### Without PHPSess:
```
Session Id: 4071pvir7unh8h4b5fap616qpv
$_COOKIE['PHPSESSID']: 4071pvir7unh8h4b5fap616qpv
Session File Name: sess_4071pvir7unh8h4b5fap616qpv
Session File Content: mypass|s:14:"secretPassword";
```

### With PHPSess:
```
Session Id: 4lpulumbs16edgq438r7dn16sj
$_COOKIE['PHPSESSID']: 4lpulumbs16edgq438r7dn16sj
Session File Name: ssess_99795dc5b9a0039b30693db1685035fe48a6f6f0f27b1fb21230736abbe62fb10f3ddfc5ee060b68d9b97a1ffb8643edfca06401e372714820a1efe8206c1c32
Session File Content: {"data":"Cu7n2AiMIVjv6WQS1wCwzVOlFgndErc\/EUbYhLj+H+8=","iv":"gracBVRT0glOyWubjlBbQQ=="}

App Key: mysecretkey
Hashed App Key: dc102045ae09982e953c44c17e207c6efa49fc4b0156f3ad5b403ae2cb521bb081794c3001f9424ad399b3a695a5a11592b13355d3d5f81aca999b0d39bb06e8
Encryption key: 2de4e95d895fdf294b0f97ae000e13ece456f7fb032c4ba394c5073825732604725c9edfde119a9cf66a65a5714763f0019ce76fb598eda5a1050c3ac895d5a1
```