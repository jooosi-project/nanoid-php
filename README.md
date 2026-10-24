# Nano ID for PHP

[Nano ID](https://github.com/ai/nanoid/tree/6.0.2) is a small, secure, URL-friendly string ID generator. This package is a PHP port of Nano ID **6.0.2**.

- Secure IDs use `random_bytes()` and unbiased sampling.
- `nanoid()` returns a 21-character ID by default, with collision resistance similar to UUID v4.
- Custom alphabets, sizes, and random-byte sources are supported.
- A separate non-secure API is available when unpredictability is not needed.

## Requirements

PHP 8.3 or later.

## Install

This fork is hosted at [jooosi-project/nanoid-php](https://github.com/jooosi-project/nanoid-php). Until the fork has its own Composer package name, it retains the `hidehalo/nanoid-php` name from its source project. To install this repository's current `main` branch, add it as a VCS repository:

```sh
composer config repositories.jooosi-nanoid vcs https://github.com/jooosi-project/nanoid-php
composer require hidehalo/nanoid-php:dev-main
```

## Generate an ID

```php
use function Hidehalo\Nanoid\nanoid;

echo nanoid();   // 21 characters
echo nanoid(10); // 10 characters
echo nanoid(0);  // empty string
```

Negative sizes throw `RangeException`.

## Custom alphabet

`customAlphabet()` returns a generator. Its second argument sets the default length; pass a length to the returned generator to override it. Secure alphabets can contain 1 to 256 Unicode code points.

```php
use const Hidehalo\Nanoid\URL_ALPHABET;
use function Hidehalo\Nanoid\customAlphabet;

$hexId = customAlphabet('0123456789abcdef', 12);
echo $hexId();   // 12 characters
echo $hexId(6);  // 6 characters

$urlId = customAlphabet(URL_ALPHABET, 10);
echo $urlId();
```

## Custom random source

`customRandom()` takes a callback that receives a byte count and returns at least that many raw bytes as a binary PHP string. The built-in `random()` helper returns cryptographically strong bytes.

```php
use function Hidehalo\Nanoid\{customRandom, random};

$generate = customRandom('0123456789abcdef', 12, random(...));
echo $generate();
```

## Non-secure IDs

The separate `NonSecure` namespace uses `mt_rand()` and is not suitable for security-sensitive IDs.

```php
use function Hidehalo\Nanoid\NonSecure\nanoid as nonSecureNanoid;

echo nonSecureNanoid();
```

## Difference from `hidehalo/nanoid-php` and migration

This repository is a fork of [`hidehalo/nanoid-php`](https://github.com/hidehalo/nanoid-php). It keeps the `Hidehalo\Nanoid` PHP namespace and Composer package name, but updates the implementation to Nano ID 6.0.2 and replaces the old `Client`, `Core`, and `GeneratorInterface` class API with functions.

The old `Client::MODE_DYNAMIC` was secure; use `nanoid()` for the same intent. The old default `Client::MODE_NORMAL` used `mt_rand()`; use `NonSecure\nanoid()` when you intentionally want non-secure IDs. Custom ID generation moves from `formattedId()` / `formatedId()` to `customAlphabet()`:

```php
// Before
$client->formattedId('0123456789abcdef', 12);

// Now
$generate = customAlphabet('0123456789abcdef', 12);
$id = $generate();
```

For a custom random source, replace a `GeneratorInterface` implementation (which returned an array of byte values) with a callback that returns raw bytes as a binary string, passed to `customRandom()`. Also note that this fork requires PHP 8.3+, and a size of `0` now returns an empty string; the old client treated non-positive sizes as the default size.

## Examples and changelog

After installing dependencies with Composer, run an example from the repository root:

```sh
php examples/basic_usage.php
php examples/custom_alphabet.php
php examples/non_secure_usage.php
```

See the [examples](examples) directory and [changelog](CHANGELOG.md).

## Credits and license

Based on [ai/nanoid](https://github.com/ai/nanoid) and forked from [hidehalo/nanoid-php](https://github.com/hidehalo/nanoid-php). MIT licensed; see [LICENSE.md](LICENSE.md).
