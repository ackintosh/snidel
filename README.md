# Snidel

A multi-process container. It looks like multi-thread-ish.

[![Latest Stable Version](https://poser.pugx.org/ackintosh/snidel/v/stable)](https://packagist.org/packages/ackintosh/snidel) [![License](https://poser.pugx.org/ackintosh/snidel/license)](https://packagist.org/packages/ackintosh/snidel) [![Build Status](https://travis-ci.org/ackintosh/snidel.svg?branch=master)](https://travis-ci.org/ackintosh/snidel)

## Installing Snidel via Composer

```
$ composer require ackintosh/snidel
```

## Usage

### Basic

```php
<?php
$func = function ($str) {
    sleep(3);
    return $str;
};

$s = time();
$snidel = new Snidel();
$snidel->fork($func, 'foo');
$snidel->fork($func, 'bar');
$snidel->fork($func, 'baz');

$snidel->wait();// optional

var_dump($snidel->get());
// * the order of results is not guaranteed. *
// array(3) {
//   [0]=>
//   string(3) "bar"
//   [1]=>
//   string(3) "foo"
//   [2]=>
//   string(3) "baz"
// }

echo (time() - $s) . 'sec elapsed' . PHP_EOL;
// 3sec elapsed.
```

### Same argument as `call_user_func_array`

```php
// multiple arguments
$snidel->fork($func, ['foo', 'bar']);

// global function
$snidel->fork('myfunction');

// instance method
$snidel->fork([$instance, 'method']);

```

### Get results with tags

```php
$snidel->fork($func, 'foo', 'tag1');
$snidel->fork($func, 'bar', 'tag1');
$snidel->fork($func, 'baz', 'tag2');

var_dump($snidel->get('tag1'));
// array(2) {
//   [0]=>
//   string(3) "foo"
//   [1]=>
//   string(3) "bar"
// }

// returns an empty array when passed unknown tags.
var_dump($snidel->get('unknown_tags'));
// array(0) {
// }
```

### maxProcs

```php
$maxProcs = 3;
$snidel = new Snidel($maxProcs);

```

### Output log

```php
fp = fopen('php://stdout', 'w');
$snidel->setLogResource($fp);

// logs are output to the `php://stdout`
$snidel->fork($func, 'foo');

// [info][26304(p)] created child process. pid: 26306
// [info][26306(c)] waiting for the token to come around.
// ...

```

## Requirements

Snidel works with PHP 5.2 or higher.

- [PCNTL functions](http://php.net/manual/en/ref.pcntl.php)
- [Semaphore functions](http://php.net/manual/en/ref.sem.php)

## Author

Akihito Nakano